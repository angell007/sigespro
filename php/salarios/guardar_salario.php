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

	if ($modelo['Id_Salario'] == '') {
		$oItem= new complex("Salario","Id_Salario");
	    $oItem->Nombre = $modelo['Nombre'];
	    $oItem->save();

	    $http_response->SetRespuesta(0, 'Registro Exitoso', 'Se ha registrado el tipo de salario exitosamente!');
	    $repsonse = $http_response->GetRespuesta();
	}else{

		$oItem= new complex("Salario","Id_Salario", $modelo['Id_Salario']);
	    $oItem->Nombre = $modelo['Nombre'];
	    $oItem->save();

	    $http_response->SetRespuesta(0, 'Actualizacion Exitosa', 'Se ha actualizado el tipo de salario exitosamente!');
	    $repsonse = $http_response->GetRespuesta();
	}

	echo json_encode($repsonse);
?>