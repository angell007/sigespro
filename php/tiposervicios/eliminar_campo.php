<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.complex.php');
	include_once('../../class/class.http_response.php');

	$http_response = new HttpResponse();
	$repsonse = array();

	$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

	$oItem = new complex("Campos_Tipo_Servicio","Id_Campos_Tipo_Servicio",$id);
	$oItem->delete();
	unset($oItem);

	$http_response->SetRespuesta(0, 'Registro Exitoso', 'Se ha registrado el tipo de activo fijo exitosamente!');
	$repsonse = $http_response->GetRespuesta();

	echo json_encode($repsonse);
?>