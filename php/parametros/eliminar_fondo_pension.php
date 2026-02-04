<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.paginacion.php');
	include_once('../../class/class.http_response.php');

	$http_response = new HttpResponse();

	$id_fondo = ( isset( $_REQUEST['id_fondo'] ) ? $_REQUEST['id_fondo'] : '' );

	$oItem = new complex("Fondo_Pension","Id_Fondo_Pension", $id_fondo);
	$oItem->delete();
	unset($oItem);

	$http_response->SetRespuesta(0, 'Eliminación Exitosa!', 'Se ha eliminado el fondo de pension correctamente!');
	$response = $http_response->GetRespuesta();

	echo json_encode($response);
?>