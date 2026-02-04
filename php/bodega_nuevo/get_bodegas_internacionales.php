<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	//require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.paginacion.php');
	include_once('../../class/class.http_response.php');
	include_once('../../class/class.utility.php');

	$http_response = new HttpResponse();
	$util = new Utility();

    $query = '
        SELECT 
            *
        FROM Bodega_Nuevo
        WHERE
            Compra_Internacional = "Si"';
	
    $queryObj = new QueryBaseDatos($query);
    $bodegas = $queryObj->Consultar('Multiple');

    unset($http_response);
    unset($queryObj);

	echo json_encode($bodegas);

?>