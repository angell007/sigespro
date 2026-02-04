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

	$id_tipo_contrato = ( isset( $_REQUEST['id_tipo_contrato'] ) ? $_REQUEST['id_tipo_contrato'] : '' );

	$query = '
		SELECT 
			Id_Tipo_Contrato,
			Nombre,
			Id_Funcionario
		FROM Tipo_Contrato
		WHERE
			Id_Tipo_Contrato ='.$id_tipo_contrato;

	//Se crea la instancia que contiene la consulta a realizar
    $queryObj = new QueryBaseDatos($query);

    //Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
    $response = $queryObj->Consultar('simple');

	echo json_encode($response);
?>