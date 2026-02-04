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

	if ($modelo['Id_Departamento_Cliente'] == '') {
		$oItem= new complex("Departamento_Cliente","Id_Departamento_Cliente");
	    $oItem->Id_Departamento = $modelo['Id_Departamento'];
	    $oItem->Id_Cliente = $modelo['Id_Cliente'];
	    $oItem->save();

	    $http_response->SetRespuesta(0, 'Registro Exitoso', 'Se ha anexado el cliente al departamento exitosamente!');
	    $repsonse = $http_response->GetRespuesta();
	}else{

		$oItem= new complex("Departamento_Cliente","Id_Departamento_Cliente", $modelo['Id_Departamento_Cliente']);
	    $oItem->Id_Departamento = $modelo['Id_Departamento'];
	    $oItem->Id_Cliente = $modelo['Id_Cliente'];
	    $oItem->save();

	    $http_response->SetRespuesta(0, 'Actualizacion Exitosa', 'Se ha actualizado el registor de cliente departamento exitosamente!');
	    $repsonse = $http_response->GetRespuesta();
	}

	echo json_encode($repsonse);
?>