<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.http_response.php');

	$modelo = (isset($_REQUEST['modelo']) && $_REQUEST['modelo'] != '') ? $_REQUEST['modelo'] : '';
	$modelo = json_decode($modelo, true);

	$response = array();
	$http_response = new HttpResponse();

	unset($modelo['Id_Respuesta_Glosa']);

	$oItem = new complex("Respuesta_Glosa","Id_Respuesta_Glosa");

	foreach($modelo as $index=>$value) {
        $oItem->$index=$value;
    }

    $oItem->save();
    unset($oItem);

	$http_response->SetRespuesta(0, 'Registro Exitoso', 'Se ha guardado la respuesta exitosamente!');
	$response = $http_response->GetRespuesta();

	echo json_encode($response);
?>