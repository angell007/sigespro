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

	$oItem = new complex("Retencion","Id_Retencion", $datos['Id_Retencion']);
	$oItem->Nombre=strtoupper($datos["Nombre"]);
	$oItem->Porcentaje=$datos["Porcentaje"];
	$oItem->Id_Plan_Cuenta=strtoupper($datos["Id_Plan_Cuenta"]);
	$oItem->Descripcion=strtoupper($datos["Descripcion"]);
	$oItem->save();
	unset($oItem);

	$http_response->SetRespuesta(0, 'Actualizacion Exitosa!', 'Se han actualizado los datos de la retencion correctamente!');
	$response = $http_response->GetRespuesta();

	echo json_encode($response);
?>