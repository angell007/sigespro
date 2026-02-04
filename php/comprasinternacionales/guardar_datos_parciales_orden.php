<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.http_response.php');
	require_once('../../class/class.configuracion.php');

	$configuracion = new Configuracion();

	$queryObj = new QueryBaseDatos();
	$response = array();
	$http_response = new HttpResponse();

	$modelo = (isset($_REQUEST['modelo']) && $_REQUEST['modelo'] != '') ? $_REQUEST['modelo'] : '';
	$otros_gastos = (isset($_REQUEST['otros_gastos']) && $_REQUEST['otros_gastos'] != '') ? $_REQUEST['otros_gastos'] : '';
	$id_funcionario = (isset($_REQUEST['id_funcionario']) && $_REQUEST['id_funcionario'] != '') ? $_REQUEST['id_funcionario'] : '';

	$modelo = (array) json_decode($modelo, true);
	$otros_gastos = (array) json_decode($otros_gastos, true);

	// var_dump($modelo);
	// var_dump($otros_gastos);
	// exit;

	$oItem = new complex("Orden_Compra_Internacional","Id_Orden_Compra_Internacional", $modelo['Id_Orden_Compra_Internacional']);

	$oItem->Flete_Internacional = $modelo['Flete_Internacional'];
	$oItem->Seguro_Internacional = $modelo['Seguro_Internacional'];
	$oItem->Flete_Nacional = $modelo['Flete_Nacional'];
	$oItem->Tramite_Sia = $modelo['Tramite_Sia'];

    $oItem->save();
    unset($oItem);

    GuardarActividadOrdenInternacional($modelo['Id_Orden_Compra_Internacional']);

    if (count($otros_gastos) > 0) {
    	GuardarOtrosGastos($otros_gastos);
    }

	$http_response->SetRespuesta(0, 'Registro Exitoso', 'Se han guardado los datos de la orden exitosamente!');
	$response = $http_response->GetRespuesta();

	echo json_encode($response);

	function GuardarOtrosGastos($otros_gastos){
		foreach ($otros_gastos as $og) {
	    	$oItem = new complex("Orden_Compra_Internacional_Otro_Gasto","Id_Orden_Compra_Internacional_Otro_Gasto");

	    	$oItem->Id_Orden_Compra_Internacional = $og['Id_Orden_Compra_Internacional'];
	    	$oItem->Concepto_Gasto = $og['Concepto_Gasto'];
	    	$oItem->Monto_Gasto = number_format($og['Monto_Gasto'], 2, ".", "");
	    	
	    	$oItem->save();
		    unset($oItem);
	    }

	    GuardarActividadOrdenInternacionalOtrosGastos($otros_gastos[0]['Id_Orden_Compra_Internacional'], $otros_gastos);
	}

	function GuardarActividadOrdenInternacional($id_orden){
		global $id_funcionario, $modelo;

		$orden_data = GetDatosCamposParcialesOrden($id_orden);
		$mensaje = ArmarMensajeActividad($orden_data, $modelo);

		if ($mensaje != '') {
			$oItem = new complex("Actividad_Orden_Compra_Internacional","Id_Actividad_Orden_Compra_Internacional");

			$oItem->Identificacion_Funcionario = $id_funcionario;
			$oItem->Id_Orden_Compra_Internacional = $id_orden;
			$oItem->Accion = "Creacion";
			$oItem->Descripcion = $mensaje;

		    $oItem->save();
		    unset($oItem);
		}

	}

	function GuardarActividadOrdenInternacionalOtrosGastos($id_orden, $otros_gastos){
		global $id_funcionario;

		$oItem = new complex("Actividad_Orden_Compra_Internacional","Id_Actividad_Orden_Compra_Internacional");

		$oItem->Identificacion_Funcionario = $id_funcionario;
		$oItem->Id_Orden_Compra_Internacional = $id_orden;
		$oItem->Accion = "Creacion";
		$oItem->Descripcion = ArmarMensajeActividadOtrosGastos($otros_gastos);

	    $oItem->save();
	    unset($oItem);
	}

	function GetDatosCamposParcialesOrden($id_orden){
		$oItem = new complex("Orden_Compra_Internacional","Id_Orden_Compra_Internacional", $id_orden);
		$datos = $oItem->getData();
	    unset($oItem);

	    return $datos;
	}

	function ArmarMensajeActividad($orden_data, $orden_req){
		$mensaje = '';

		if ($orden_data['Flete_Internacional'] != $orden_req['Flete_Internacional']) {
			if ($mensaje == '') {
				$mensaje = 'Se modificaron los siguientes valores Flete Internacional por valor de '.$orden_req['Flete_Internacional'].', ';
			}else{
				$mensaje .= 'Flete Internacional por valor de '.$orden_req['Flete_Internacional'].', ';				
			}
		}

		if ($orden_data['Seguro_Internacional'] != $orden_req['Seguro_Internacional']) {
			if ($mensaje == '') {
				$mensaje = 'Se modificaron los siguientes valores Seguro Internacional por valor de '.$orden_req['Seguro_Internacional'].', ';
			}else{
				$mensaje .= 'Seguro Internacional por valor de '.$orden_req['Seguro_Internacional'].', ';			
			}			
		}

		if ($orden_data['Flete_Nacional'] != $orden_req['Flete_Nacional']) {
			if ($mensaje == '') {
				$mensaje = 'Se modificaron los siguientes valores Flete Nacional por valor de '.$orden_req['Flete_Nacional'].', ';
			}else{
				$mensaje .= 'Flete Nacional por valor de '.$orden_req['Flete_Nacional'].', ';			
			}
		}

		if ($orden_data['Tramite_Sia'] != $orden_req['Tramite_Sia']) {
			if ($mensaje == '') {
				$mensaje = 'Se modificaron los siguientes valores Tramite Sia por valor de '.$orden_req['Tramite_Sia'].', ';
			}else{
				$mensaje .= 'Tramite Sia por valor de '.$orden_req['Tramite_Sia'].', ';			
			}
		}

	    return trim($mensaje, ", ");
	}

	function ArmarMensajeActividadOtrosGastos($otros_gastos){
		$mensaje = 'Se adicionaron los siguientes gastos ';

		foreach ($otros_gastos as $og) {
	    	$mensaje .= $og['Concepto_Gasto']." por un monto de $ ".$og['Monto_Gasto'].", ";
	    }

	    return trim($mensaje, ", ");
	}
?>