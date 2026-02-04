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

	$id_riesgo = ( isset( $_REQUEST['id_tipo_riesgo'] ) ? $_REQUEST['id_tipo_riesgo'] : '' );

	$query = '
		SELECT 
			*
		FROM Riesgo
		WHERE
			Id_Riesgo ='.$id_riesgo;

	$query_plan = '
		SELECT
			R.Id_Plan_Cuentas,
			PC.Nombre
		FROM Riesgo R
		INNER JOIN Plan_Cuentas PC ON R.Id_Plan_Cuentas = PC.Id_Plan_Cuentas
		WHERE
			Id_Riesgo ='.$id_riesgo;

	//Se crea la instancia que contiene la consulta a realizar
    $queryObj = new QueryBaseDatos($query);

    //Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
    $response = $queryObj->Consultar('simple');

	$queryObj->setQuery($query_plan);
	$response['Plan'] = $queryObj->ExecuteQuery('simple');

	unset($queryObj);

	echo json_encode($response);
?>