<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');


	require_once('../../config/start.inc.php');
	include_once('../../class/class.lista.php');
	include_once('../../class/class.complex.php');
	require_once('../../class/class.configuracion.php');
	include_once('../../class/class.consulta.php');
	include_once('../../class/class.http_response.php');

	$http_response = new HttpResponse();

	$id_punto_cronograma = ( isset( $_REQUEST['id_punto_cronograma'] ) ? $_REQUEST['id_punto_cronograma'] : '' );

	$oItem=new complex('Punto_Cronograma_Remision',"Id_Punto_Cronograma_Remision", $id_punto_cronograma);
    $oItem->delete();
    unset($oItem);

	$http_response->SetRespuesta(0, 'Eliminacion Exitoso', 'Se han eliminado el punto del cronograma exitosamente!');
	$response = $http_response->GetRespuesta();

	echo json_encode($response);

?>