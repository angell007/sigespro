<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.complex.php');
	include_once('../../class/class.configuracion.php');
	include_once('../../class/class.http_response.php');
	include_once('../../class/class.querybasedatos.php');

	$http_response = new HttpResponse();
	$queryObj = new QueryBaseDatos();
	$response = array();

	$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );
	$servicios = ( isset( $_REQUEST['servicios'] ) ? $_REQUEST['servicios'] : '' );
	$tipos_servicio = ( isset( $_REQUEST['tipos_servicio'] ) ? $_REQUEST['tipos_servicio'] : '' );

	$modelo = json_decode($modelo, true);
	$servicios = json_decode($servicios, true);
	$tipos_servicio = json_decode($tipos_servicio, true);

	// var_dump($modelo);
	// var_dump($servicios);
	// var_dump($tipos_servicio);
	// exit;

    GuardarPuntoDispensacion($modelo);
    GuardarServiciosPuntoDispensacion($servicios, $modelo['id']);
    $tipos_servicio = ValidarTiposServicios($servicios, $tipos_servicio);
    GuardarTipoServiciosPuntoDispensacion($tipos_servicio, $modelo['id']);

    $http_response->SetRespuesta(0, 'Registro Exitoso', 'Se ha registrado el punto de dispensacion exitosamente!');
    $response = $http_response->GetRespuesta();

	echo json_encode($response);

	function GuardarPuntoDispensacion($modelo){

		$oItem= new complex("Punto_Dispensacion","Id_Punto_Dispensacion", $modelo['id']);

		foreach($modelo as $index=>$value) {
		    $oItem->$index=$value;
		}
		
	    $oItem->save();
	    unset($oItem);

	    return $id_punto;
	}

	function GuardarServiciosPuntoDispensacion($servicios, $idPunto){
		global $queryObj;

		$query_delete = 'DELETE FROM Servicio_Punto_Dispensacion WHERE Id_Punto_Dispensacion = '.$idPunto;
		$queryObj->SetQuery($query_delete);
		$queryObj->QueryUpdate();

		foreach ($servicios as $service) {
			$oItem= new complex("Servicio_Punto_Dispensacion","Id_Servicio_Punto_Dispensacion");
		    $oItem->Id_Punto_Dispensacion =$idPunto;
		    $oItem->Id_Servicio =$service;
	    	$oItem->save();
		    unset($oItem);
		}
	}

	function GuardarTipoServiciosPuntoDispensacion($tipoServicios, $idPunto){
		global $queryObj;

		$query_delete = 'DELETE FROM Tipo_Servicio_Punto_Dispensacion WHERE Id_Punto_Dispensacion = '.$idPunto;
		$queryObj->SetQuery($query_delete);
		$queryObj->QueryUpdate();

		foreach ($tipoServicios as $ts) {
			$oItem= new complex("Tipo_Servicio_Punto_Dispensacion","Id_Tipo_Servicio_Punto_Dispensacion");
		    $oItem->Id_Punto_Dispensacion =$idPunto;
		    $oItem->Id_Tipo_Servicio =$ts;
	    	$oItem->save();
		    unset($oItem);
		}
	}

	function ValidarTiposServicios($servicios, $tiposServicio){
		global $queryObj;

		$condicion_servicios = MakeInCondition($servicios);
		$tipoServicioFinal = $tiposServicio;


		foreach ($tiposServicio as $key => $id) {
			
			$query = '
				SELECT
					Id_Tipo_Servicio
				FROM Servicio S
				INNER JOIN Tipo_Servicio TS ON S.Id_Servicio = TS.Id_Servicio
				WHERE 
					TS.Id_Servicio IN ('.$condicion_servicios.')
					AND TS.Id_Tipo_Servicio = '.$id;

			$queryObj->SetQuery($query);
			$exist = $queryObj->ExecuteQuery('simple');

			if ($exist === false) {
				unset($tipoServicioFinal[$key]);
			}
		}

		return $tipoServicioFinal;
	}

	function MakeInCondition($servicios){
		$condicion = implode(", ", $servicios);
		return $condicion;
	}
?>