Get Started
=====

# 修改百度UEditor 单文件上传七牛云等第三方云服务 导致的跨域问题。

> 由于官方文档中对于多文件和涂鸦上传使用的是 flash 和 h5的方式，但是对于单文件上传使用的是提交隐藏iframe方式。因此在跨域的时候，单纯的修改 document.domain 并不能解决问题。对该问题官方文档也没有给出详细的说明。本文主要针对该问题进行一个补丁修改。

## 1. 修改配置文件

修改 ueditor.config.js 

``` js

// 服务器统一请求接口路径
, serverRoot: "http://bb.com"  //该行为新增
, serverUrl: serverRoot + "php/controller.php"

//若实例化编辑器的页面手动修改的domain，此处需要设置为true
,customDomain: false

//手动修改页面的domain值, 这一行为新增
,customDomainValue:'aa.com'

```

bbb.com/ 为要跨域的目标服务器域名

## 2. 为上传的页面设置 document.domain

通常，我们一旦给某个页面设置了 document.domain 那么即使你访问的目标网址在同源下，也会出现跨域的错误提示，除非被访问的页面也设置该属性（此处如有错误欢迎指正），但是document.domain 值只能设置为同一个顶级域名下，否则还是会出现错误提示，也就是说
test1.aa.com 和 test2.aa.com 可以指定相同的 document.domain = "aa.com"; 而 如果 是 test1.aa.com 和 test2.bb.com 则无法指定相同的document.domain，会报错。

解释了一大堆，我们的目的是为调用ueditor的页面设置一个 document.domain， 在ueditor.config.js 结尾处

``` js
  window.UE = {
        getUEBasePath: getUEBasePath
    };

	//新增下面一行，为ueditor 指定 document.domain属性
    document.domain = window.UEDITOR_CONFIG.customDomainValue;
```

## 3. 在 simple.upload.js中修改 单文件提交的 action 值，用来在服务的进行判断

> 如果是 ueditor.all.js 则找到 simpleupload 所在的位置。
修改如下位置
``` js
var imageActionUrl = me.getActionUrl(me.getOpt('imageActionName'));
```

改为

``` js
var imageActionUrl = me.getActionUrl(me.getOpt('imageActionName')) + '&callback=crossdomain&customDomainValue=' + me.getOpt('customDomainValue');
```
再修改写会的值
```js
link = me.options.imageUrlPrefix + json.url;

//修改为
link = me.getOpt('serverRoot') + me.options.imageUrlPrefix + json.url;
```


## 4. 修改服务端（该服务端在域名b.com下），根据参数进行判断，将获取的结果传回原域名下的服务端接口，用于接收。

> 以PHP为例，修改 controller.php (这个为入口文件)

```php
if (preg_match("/^[\w_]+$/", $_GET["callback"])) {
	echo htmlspecialchars($_GET["callback"]) . '(' . $result . ')';
}
```

修改为

``` php
if (preg_match("/^[\w_]+$/", $_GET["callback"])) {

	//处理单文件跨域上传
	if($_GET["callback"] == 'crossdomain'){

		if (preg_match("[a-zA-Z0-9][-a-zA-Z0-9]{0,62}(/.[a-zA-Z0-9][-a-zA-Z0-9]{0,62})+/.?", $_GET["customDomainValue"])){
			header('Location: ' . $_GET["customDomainValue"] . '/api/get_params.php?customDomainValue=' . $_GET["customDomainValue"] . 'result=' . $result);
		}
	}else{
		echo htmlspecialchars($_GET["callback"]) . '(' . $result . ')';
	}

}
```

## 5. 在 aa.com 下新建文件 aa.com/api/get_params.php 用于跨域返回值。

内容类似下面
```php
<?php
if(isset($_GET['result'])){
	if (preg_match("[a-zA-Z0-9][-a-zA-Z0-9]{0,62}(/.[a-zA-Z0-9][-a-zA-Z0-9]{0,62})+/.?", $_GET["customDomainValue"])){
		echo '<html><head><script>document.domain="' . $_GET["customDomainValue"] . '"</script></head><body>'. $_GET['result'] .'</body></html>';
	}
}
```
