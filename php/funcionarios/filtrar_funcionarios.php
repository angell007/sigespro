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
			Identificacion_Funcionario AS Id,
			CONCAT_WS(" ", Nombres, Apellidos) AS Nombre_Funcionario
		FROM Funcionario
		WHERE
			Identificacion_Funcionario LIKE "%'.$match.'%" OR CONCAT_WS(" ", Nombres, Apellidos) LIKE "%'.$match.'%"'; 


    $queryObj = new QueryBaseDatos($query);
    $matches = $queryObj->ExecuteQuery('Multiple');

	echo json_encode($matches);
?>