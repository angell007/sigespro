<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.paginacion.php');
	include_once('../../class/class.http_response.php');

	$match = ( isset( $_REQUEST['coincidencia'] ) ? $_REQUEST['coincidencia'] : '' );

	$http_response = new HttpResponse();

	$query = '
		SELECT
			T.*
		FROM (SELECT 
                Id_Plan_Cuentas AS Id,
                Codigo,
				CONCAT_WS(" ", Codigo," - ",Nombre) AS Nombre,
				Movimiento
			FROM Plan_Cuentas
				) T
		WHERE
			(T.Codigo LIKE "%'.$match.'%" OR T.Nombre LIKE "%'.$match.'%") AND T.Movimiento = "S"'; 


    $queryObj = new QueryBaseDatos($query);
    $matches = $queryObj->ExecuteQuery('Multiple');

	echo json_encode($matches);
?>