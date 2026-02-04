<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.http_response.php');

	$http_response = new HttpResponse();

	$id_estado_financiero = ( isset( $_REQUEST['id_estado_financiero'] ) ? $_REQUEST['id_estado_financiero'] : '' );

	$oItem = new complex("Estado_Financiero","Id_Estado_Financiero", $id_estado_financiero);
	$oItem->delete();
	unset($oItem);

	$http_response->SetRespuesta(0, 'Eliminacion Exitosa!', 'Se ha eliminado el estado financiero correctamente!');
	$response = $http_response->GetRespuesta();

	echo json_encode($response);
?>