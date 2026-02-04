<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.paginacion.php');
	include_once('../../class/class.http_response.php');

	$match = ( isset( $_REQUEST['coincidencia'] ) ? $_REQUEST['coincidencia'] : '' );

	$http_response = new HttpResponse();

	$query = '
		SELECT 
			Id_Plan_Cuentas,
			Nombre
		FROM Plan_Cuentas
		WHERE  
			Estado = "ACTIVO" 
			AND Nombre LIKE "%'.$match.'%"
		ORDER BY Nombre ASC'; 

	//Se crea la instancia que contiene la consulta a realizar
    $queryObj = new QueryBaseDatos($query);

    //Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
    $tipos_contrato = $queryObj->ExecuteQuery('Multiple');

	echo json_encode($tipos_contrato);
?>