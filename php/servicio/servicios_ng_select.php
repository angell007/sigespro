<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.paginacion.php');
	include_once('../../class/class.http_response.php');

	$http_response = new HttpResponse();

    $query = '
    SELECT 
    	Id_Servicio AS value,
    	Nombre AS label 
	FROM Servicio 
    ORDER BY Nombre DESC ';

	$queryObj = new QueryBaseDatos($query);

	//Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
	$servicios = $queryObj->Consultar('Multiple');

	echo json_encode($servicios);
?>