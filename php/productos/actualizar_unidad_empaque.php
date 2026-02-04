<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.http_response.php');

	$queryObj = new QueryBaseDatos();
	$response = array();
	$http_response = new HttpResponse();

	$id_producto = ( isset( $_REQUEST['id_producto'] ) ? $_REQUEST['id_producto'] : '' );
	$empaque = ( isset( $_REQUEST['empaque'] ) ? $_REQUEST['empaque'] : '' );

	ActualizarUnidadEmpaque($id_producto, $empaque);

	$http_response->SetRespuesta(0, 'Actualizacion Exitosa', 'Se ha actualizado la unidad de empaque con exito!');
	$response = $http_response->GetRespuesta();

	echo json_encode($response);

	function ActualizarUnidadEmpaque($id_producto, $empaque){
		global $queryObj;

		$query = 'UPDATE Producto SET Unidad_Empaque = '.$empaque.' WHERE Id_Producto = '.$id_producto;

		$queryObj->SetQuery($query);
		$queryObj->QueryUpdate();
	}
?>