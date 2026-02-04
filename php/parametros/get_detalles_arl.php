<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.http_response.php');

	$http_response = new HttpResponse();

	$id_arl = ( isset( $_REQUEST['id_arl'] ) ? $_REQUEST['id_arl'] : '' );

	$query = '
		SELECT 
            *
		FROM Arl
        WHERE
            Id_Arl = '.$id_arl;

    $queryObj = new QueryBaseDatos($query);
    $fondo_pension = $queryObj->Consultar('simple');

	echo json_encode($fondo_pension);
?>