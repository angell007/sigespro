<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	include_once('../../class/class.complex.php');
	include_once('../../class/class.http_response.php');

	$tipo = $_REQUEST['tipo'];
	$id = $_REQUEST['id'];

	$http_response = new HttpResponse();
	$response = [];

	if ($tipo && $id) {
		$oItem = new complex($tipo,"Id_$tipo",$id);
		$data = $oItem->getData();
		$oItem->Estado = $data['Estado'] == 'Activo' ? 'Inactivo' : 'Activo';
		$oItem->save();
		unset($oItem);

		$http_response->SetRespuesta(0, 'Exito!', 'Se ha cambiado el estado correctamente del '.$tipo);
		$response = $http_response->GetRespuesta();

	} else {
		$http_response->SetRespuesta(2, 'Error!', 'Ha ocurrido un error al procesar la información.');
		$response = $http_response->GetRespuesta();
	}

	echo json_encode($response);
	
?>