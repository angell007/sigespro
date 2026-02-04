<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../config/start.inc.php');
	include_once('../class/class.querybasedatos.php');
	include_once('../class/class.paginacion.php');
	include_once('../class/class.http_response.php');

	$http_response = new HttpResponse();

    $query = '
    SELECT 
    	Id_Paciente, 
    	Primer_Nombre, 
    	Segundo_Nombre, 
    	Primer_Apellido, 
    	Segundo_Apellido 
	FROM Paciente
	WHERE
		Id_Paciente = 1000084128';

	$queryObj = new QueryBaseDatos($query);

	//Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
	$record = $queryObj->ExecuteQuery('simple');
	var_dump($record);
	echo json_encode(utf8_decode($record['Primer_Apellido']));
?>