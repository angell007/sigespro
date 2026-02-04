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
	$facturas = ( isset( $_REQUEST['facturas'] ) ? $_REQUEST['facturas'] : '' );
	$modelo = json_decode($modelo, true);
	$facturas = json_decode($facturas, true);
	$fecha = date('Y-m-d H:i:s');

	// var_dump($facturas);
	// var_dump($modelo);
	// exit;

	$config = new Configuracion();
	$cod = $config->Consecutivo('Radicacion');
	$modelo["Codigo"]=$cod;

	if (count($facturas) == 0) {
		
		$http_response->SetRespuesta(2, 'Alerta', 'No hay facturas para realizar el registro, verifique o contacte al administrador del sistema!');
		$response = $http_response->GetRespuesta();
		echo json_encode($response);
		return;
	}

	if ($modelo['Id_Radicado'] == '') {
		$oItem= new complex("Radicado","Id_Radicado");
		/*foreach($datos as $index=>$value) {
			if ($index == 'Id_Radicado' && $value == '') {
				

			}
		    $oItem->$index=$value;
		}*/

		if ($modelo['Consecutivo'] != '') {
			$oItem->Consecutivo = $modelo['Consecutivo'];
		}

		if ($modelo['Numero_Radicado'] != '') {
			$oItem->Numero_Radicado = $modelo['Numero_Radicado'];
		}

		if ($modelo['Fecha_Radicado'] != '') {
			$oItem->Fecha_Radicado = $modelo['Fecha_Radicado'];
		}

		if ($modelo['Observacion'] != '') {
	    	$oItem->Observacion = $modelo['Observacion'];
		}else{
			$oItem->Observacion = 'Radicacion guardada sin informacion extra!';
		}

		$id_tipo_servicio = $modelo['Tipo_Servicio'] == '' ? "0" : $modelo['Tipo_Servicio'];
		$nombre_tipo_servicio = $modelo['Tipo_Servicio'] == '' ? "TODOS" : GetNombreTipoServicio($modelo['Tipo_Servicio']);
		$departamento = $modelo['Id_Departamento'] == '' ? '0' : $modelo['Id_Departamento'];
		$estado_rad = $model['Numero_Radicado'] == '' ? 'PreRadicada' : 'Radicada';

	    $oItem->Id_Funcionario = $modelo['Id_Funcionario'];
	    $oItem->Id_Cliente = $modelo['Id_Cliente'];
	    $oItem->Id_Departamento = $departamento;
	    $oItem->Id_Regimen = $modelo['Id_Regimen'];
	    $oItem->Tipo_Servicio = $nombre_tipo_servicio;
	    $oItem->Id_Tipo_Servicio = $id_tipo_servicio;
	    $oItem->Codigo = $modelo['Codigo'];
	    $oItem->Fecha_Registro = $fecha;
	    $oItem->Estado = $estado_rad;
	    $oItem->save();
	    $id_radicado = $oItem->getId();
	    unset($oItem);

	    GuardarFacturasRadicadas($facturas, $id_radicado, $nombre_tipo_servicio);
	    GuardarActividadRadicado($id_radicado, $modelo['Id_Funcionario'], $modelo['Codigo'], count($facturas));

	    $http_response->SetRespuesta(0, 'Registro Exitoso', 'Se ha registrado la radicacion exitosamente!');
	    $response = $http_response->GetRespuesta();
	}else{
	}

	echo json_encode($response);

	function GuardarFacturasRadicadas($facturas, $idRadicado, $nombreTipoServicio){

		foreach ($facturas as $factura) {
			
			$oItem= new complex("Radicado_Factura","Id_Radicado_Factura");
		    $oItem->Id_Radicado = $idRadicado;
		    $oItem->Id_Factura = $factura['Id_Factura'];
		    $oItem->save();
		    unset($oItem);

		    if (strtolower($nombreTipoServicio) == 'capita') {
		    	$oItem= new complex("Factura_Capita", "Id_Factura_Capita", $factura['Id_Factura']);
			    $oItem->Estado_Radicacion = 'Radicada';
			    $oItem->save();
			    unset($oItem);
		    }else{
		    	$oItem= new complex("Factura", "Id_Factura", $factura['Id_Factura']);
			    $oItem->Estado_Radicacion = 'Radicada';
			    $oItem->save();
			    unset($oItem);
		    }

		   
		}
	}

	function GuardarActividadRadicado($idRadicado, $idFuncionario, $codigo, $totalFacturas){
			
		$oItem= new complex("Actividad_Radicado","Id_Actividad_Radicado");
	    $oItem->Id_Funcionario = $idFuncionario;
	    $oItem->Id_Radicado = $idRadicado;
	    $oItem->Detalle = "Se creo la radicacion con codigo ".$codigo." con un total de ".$totalFacturas." factura(s)";
	    $oItem->Estado = 'Creacion';
	    $oItem->save();
	    unset($oItem);
	}

	function GetNombreTipoServicio($idTipoServicio){
		$oItem= new complex("Tipo_Servicio","Id_Tipo_Servicio", $idTipoServicio);
	    $nombre_servicio = $oItem->Nombre;
	    unset($oItem);

	    return $nombre_servicio;
	}
?>