<?
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	require_once('../config/start.inc.php');

	var_dump($MY_FILE);
	var_dump($_SERVER["DOCUMENT_ROOT"]);
	var_dump($_SERVER['HTTP_HOST']);
?>