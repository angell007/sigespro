<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	//require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.paginacion.php');
	include_once('../../class/class.http_response.php');

	$http_response = new HttpResponse();

	$id_glosa = ( isset( $_REQUEST['id_glosa'] ) ? $_REQUEST['id_glosa'] : '' );

	$fecha = date('Y-m-d');

    $query = '
        SELECT 
            RG.*,
            (SELECT COUNT(Id_Respuesta_Glosa) FROM Respuesta_Glosa WHERE Id_Respuesta_Glosa = RG.Id_Respuesta_Glosa) AS Cantidad_Respuestas    
        FROM Respuesta_Glosa RG
        WHERE
            Id_Respuesta
        ORDER BY Fecha_Respuesta DESC';

    $queryObj = new QueryBaseDatos($query);
    $respuestas_glosa = $queryObj->Consultar('Multiple');

	echo json_encode($respuestas_glosa);
?>