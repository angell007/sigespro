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
    $queryObj = new QueryBaseDatos();

    $id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

    $query= DataPositiva();

    $queryObj->SetQuery($query);

    $data = $queryObj->ExecuteQuery('Multiple');
    
    echo json_encode($data);


    function DataPositiva(){

        global $id;
        $query = 'SELECT Detalle_Estado
                     FROM Positiva_Data
                  WHERE id = '.$id;
    
        return $query;

        

       
    }