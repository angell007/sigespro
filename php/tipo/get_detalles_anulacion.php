<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.http_response.php');

	$http_response = new HttpResponse();

	$id_anulacion = ( isset( $_REQUEST['id_anulacion'] ) ? $_REQUEST['id_anulacion'] : '' );

	$query = '
		SELECT 
            *
		FROM Causal_Anulacion
        WHERE
            Id_Causal_Anulacion = '.$id_anulacion;

    $queryObj = new QueryBaseDatos($query);
    $causal_anulacion = $queryObj->Consultar('simple');

	echo json_encode($causal_anulacion);
?>