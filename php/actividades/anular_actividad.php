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

	$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

	if ($id) {
		$oItem= new complex("Actividad","Id_Actividad",$id);
	    $oItem->Estado = 'Anulada';
	    $oItem->save();
	    unset($oItem);

	    $http_response->SetRespuesta(0, 'Registro Exitoso', 'Se ha anulado la actividad exitosamente!');
	    $response = $http_response->GetRespuesta();
	} 

	echo json_encode($response);
?>