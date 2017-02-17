<?php
if(isset($_GET['result'])){
	if (preg_match("[a-zA-Z0-9][-a-zA-Z0-9]{0,62}(/.[a-zA-Z0-9][-a-zA-Z0-9]{0,62})+/.?", $_GET["customDomainValue"])){
		echo '<html><head><script>document.domain="' . $_GET["customDomainValue"] . '"</script></head><body>'. $_GET['result'] .'</body></html>';
	}    
}