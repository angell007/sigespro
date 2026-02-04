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

	$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );
	$modelo = json_decode($modelo, true);

	$fecha = date('Y-m-d H:i:s');

	

	if ($modelo['Id_Actividad'] == '') {
		$oItem= new complex("Actividad","Id_Actividad");
	    $oItem->Actividad = $modelo['Actividad'];
	    $oItem->Identificacion_Funcionario = $modelo['Identificacion_Funcionario'];
	    $oItem->Id_Cliente = $modelo['Id_Cliente'];
	    $oItem->Fecha_Inicio = $modelo['Fecha_Inicio'];
	    $oItem->Fecha_Fin = $modelo['Fecha_Fin'];
	    $oItem->Id_Tipo_Actividad = $modelo['Id_Tipo_Actividad'];
	    $oItem->Estado = "Aprobada";
	    $oItem->Fecha_Cambio_Estado= $fecha;
	    $oItem->Id_Funcionario_Cambio_Estado= $modelo['Id_Funcionario_Cambio_Estado'];
	    $oItem->Detalles= $modelo['Detalles'];
	    $oItem->save();
	    unset($oItem);

	    $http_response->SetRespuesta(0, 'Registro Exitoso', 'Se ha registrado la actividad exitosamente!');
	    $response = $http_response->GetRespuesta();
	}

	echo json_encode($response);
?>