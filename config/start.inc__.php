<?php
	require_once ("config.inc.php");
	setlocale(LC_ALL, 'spanish');

	global $DEVELOP_ENVIRONMENT;
	
	if ($DEVELOP_ENVIRONMENT==true) {
		error_reporting(E_ALL);
	} else {
		error_reporting(0);
	}
?>