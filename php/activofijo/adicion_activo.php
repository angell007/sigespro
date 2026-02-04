<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.complex.php');
	include_once('../../class/class.http_response.php');

	$http_response = new HttpResponse();
	$response = array();

	$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;
	$adicion = isset($_REQUEST['adicion']) ? $_REQUEST['adicion'] : false;

	$oItem = new complex('Adicion_Activo_Fijo','Id_Adicion_Activo_Fijo');
	$oItem->Adicion = number_format($adicion,2,".","");
	$oItem->Id_Activo_Fijo = $id;
	$oItem->Fecha_Adicion = date('Y-m-d H:i:s');
	$oItem->save();
	unset($oItem);

	$oItem = new complex('Activo_Fijo', 'Id_Activo_Fijo', $id);
	$oItem->Ultima_Adicion = number_format($adicion,2,".","");
	$oItem->Fecha_Ultima_Adicion = date('Y-m-d H:i:s');
	$oItem->save();
	unset($oItem);

	$http_response->SetRespuesta(0, 'Exito!', 'Se ha registrado la adicion correctamente!');
	$response = $http_response->GetRespuesta();

	echo json_encode($response);
?>