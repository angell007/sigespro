<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../../config/start.inc.php');
	include_once('../../../class/class.complex.php');
	include_once('../../../class/class.configuracion.php');
	include_once('../../../class/class.http_response.php');

	$http_response = new HttpResponse();
	$response = array();

	$id_controlado = ( isset( $_REQUEST['id_controlado'] ) ? $_REQUEST['id_controlado'] : '' );

	$oItem= new complex("Producto_Control_Cantidad","Id_Producto_Control_Cantidad", $id_controlado);
	$oItem->delete();

    $http_response->SetRespuesta(0, 'Eliminacion Exitosa', 'Se ha eliminado el control de cantidad del producto exitosamente!');
    $response = $http_response->GetRespuesta();

	unset($oItem);
	unset($http_response);

	echo json_encode($response);
?>