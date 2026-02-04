<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.http_response.php');

	$http_response = new HttpResponse();

	$id_caja_compensacion = ( isset( $_REQUEST['id_caja_compensacion'] ) ? $_REQUEST['id_caja_compensacion'] : '' );

	$query = '
		SELECT 
            *
		FROM Caja_Compensacion
        WHERE
            Id_Caja_Compensacion = '.$id_caja_compensacion;

    $queryObj = new QueryBaseDatos($query);
    $fondo_pension = $queryObj->Consultar('simple');

	echo json_encode($fondo_pension);
?>