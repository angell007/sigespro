<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../../config/start.inc.php');
	include_once('../../../class/class.querybasedatos.php');
	include_once('../../../class/class.http_response.php');

	$http_response = new HttpResponse();

	$query = '
		SELECT 
			Id_Tipo_Servicio,
			Nombre, 
			Codigo
		FROM Tipo_Servicio
		WHERE
			Nombre <> "EVENTO"
		ORDER BY Nombre ASC';

	//Se crea la instancia que contiene la consulta a realizar
    $queryObj = new QueryBaseDatos($query);

    //Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
    $tipos = $queryObj->Consultar('Multiple');

    if (count($tipos['query_result']) > 0) {
    	
    	$evento['Id_Tipo_Servicio'] = 'EVENTO';
    	$evento['Nombre'] = 'EVENTO';
    	$evento['Codigo'] = 'EVENTO';
    	$tipos['query_result'][] = $evento;

    	$capita['Id_Tipo_Servicio'] = 'CAPITA';
    	$capita['Nombre'] = 'CAPITA';
    	$capita['Codigo'] = 'CAPITA';
    	$tipos['query_result'][] = $capita;
    }

	echo json_encode($tipos);
?>