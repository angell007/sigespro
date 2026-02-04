<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.http_response.php');

	$http_response = new HttpResponse();

	$id_subcategoria = ( isset( $_REQUEST['id_subcategoria'] ) ? $_REQUEST['id_subcategoria'] : '' );

	$query = '
		SELECT 
            *
		FROM Subcategoria
        WHERE
            Id_Subcategoria = '.$id_subcategoria;

    $queryObj = new QueryBaseDatos($query);
    $fondo_pension = $queryObj->Consultar('simple');

	echo json_encode($fondo_pension);
?>