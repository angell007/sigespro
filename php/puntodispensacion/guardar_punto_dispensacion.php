<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.complex.php');
	include_once('../../class/class.configuracion.php');
	include_once('../../class/class.http_response.php');

	$http_response = new HttpResponse();
	$response = array();

	$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );
	$servicios = ( isset( $_REQUEST['servicios'] ) ? $_REQUEST['servicios'] : '' );
	$tipos_servicio = ( isset( $_REQUEST['tipos_servicio'] ) ? $_REQUEST['tipos_servicio'] : '' );



	$modelo = json_decode($modelo, true);
	$servicios = json_decode($servicios, true);
	$tipos_servicio = json_decode($tipos_servicio, true);

	//  var_dump($modelo);
	// var_dump($servicios);
	// var_dump($tipos_servicio);
	// exit;

    $id_punto = GuardarPuntoDispensacion($modelo);
    GuardarServiciosPuntoDispensacion($servicios, $id_punto);
    GuardarTipoServiciosPuntoDispensacion($tipos_servicio, $id_punto);

    $http_response->SetRespuesta(0, 'Registro Exitoso', 'Se ha registrado el punto de dispensacion exitosamente!');
    $response = $http_response->GetRespuesta();

	echo json_encode($response);

	function GuardarPuntoDispensacion($modelo){

		$oItem= new complex("Punto_Dispensacion","Id_Punto_Dispensacion");

		foreach($modelo as $index=>$value) {
		    $oItem->$index=$value;
		}
		
	    $oItem->save();
	    $id_punto = $oItem->getId();
	    unset($oItem);

	    return $id_punto;
	}

	function GuardarServiciosPuntoDispensacion($servicios, $idPunto){

		foreach ($servicios as $service) {
			$oItem= new complex("Servicio_Punto_Dispensacion","Id_Servicio_Punto_Dispensacion");
		    $oItem->Id_Punto_Dispensacion =$idPunto;
		    $oItem->Id_Servicio =$service;
	    	$oItem->save();
		    unset($oItem);
		}
	}

	function GuardarTipoServiciosPuntoDispensacion($tipoServicios, $idPunto){

		foreach ($tipoServicios as $ts) {
			$oItem= new complex("Tipo_Servicio_Punto_Dispensacion","Id_Tipo_Servicio_Punto_Dispensacion");
		    $oItem->Id_Punto_Dispensacion =$idPunto;
		    $oItem->Id_Tipo_Servicio =$ts;
	    	$oItem->save();
		    unset($oItem);
		}
	}
?>