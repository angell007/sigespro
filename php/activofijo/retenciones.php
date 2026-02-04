<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.paginacion.php');
	include_once('../../class/class.http_response.php');

	
	$http_response = new HttpResponse();

	$query = 'SELECT R.*
			FROM Retencion R'; 


    $queryObj = new QueryBaseDatos($query);
    $retenciones = $queryObj->ExecuteQuery('Multiple');

	echo json_encode($retenciones);
?>