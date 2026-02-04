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

	$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );
    $modelo = json_decode($modelo, true);
    $fecha = date('Y-m-d H:i:s');
	$oItem= new complex("Servicio","Id_Servicio", $modelo['Id_Servicio']);	 
	$oItem->Estado="Inactivo";	 
	$oItem->Funcionario_Inactiva=$modelo['Funcionario_Inactiva'];	 
	$oItem->Fecha_Inactivacion=$fecha;	 
	$oItem->save();
	unset($oItem);

	$http_response->SetRespuesta(0, 'Actualizacion Exitosa', 'Se ha inactivado el servicio exitosamente!');
	$repsonse = $http_response->GetRespuesta();


	echo json_encode($repsonse);
?>