<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.http_response.php');

	$http_response = new HttpResponse();

	$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
	$datos = (array) json_decode($datos);

	$oItem = new complex("Arl","Id_Arl", $datos['Id_Arl']);
	$oItem->Nombre=strtoupper($datos["Nombre"]);
	$oItem->Codigo=$datos["Codigo"];
	$oItem->Nit=$datos["Nit"];
	$oItem->save();
	unset($oItem);

	$http_response->SetRespuesta(0, 'Actualizacion Exitosa!', 'Se han actualizado los datos del ARL correctamente!');
	$response = $http_response->GetRespuesta();

	echo json_encode($response);
?>