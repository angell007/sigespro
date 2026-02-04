<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.complex.php');
	include_once('../../class/class.consulta.php');
	include_once('../../class/class.http_response.php');
	include_once('../../class/class.utility.php');

	$util = new Utility();
	$http_response = new HttpResponse();
	$response = array();

	$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );
	$facturas = ( isset( $_REQUEST['facturas'] ) ? $_REQUEST['facturas'] : '' );
	$id_facturas = ( isset( $_REQUEST['id_facturas'] ) ? $_REQUEST['id_facturas'] : '' );
	$cerrar_radicacion = ( isset( $_REQUEST['cerrar'] ) ? $_REQUEST['cerrar'] : '' );
	$modelo = json_decode($modelo, true);
	$facturas = json_decode($facturas, true);
	$id_facturas = json_decode($id_facturas, true);
	$fecha = date('Y-m-d');

	/*var_dump($modelo);
	var_dump($facturas);
	var_dump($id_facturas);
	var_dump($cerrar_radicacion);
	exit;*/

	if (count($facturas) == 0) {
		
		$http_response->SetRespuesta(2, 'Alerta', 'No hay facturas para actualizar el registro, verifique o contacte al administrador del sistema!');
		$response = $http_response->GetRespuesta();
		echo json_encode($response);
		return;
	}


	//SE ACTUALIZAN LAS FACTURAS
	foreach ($facturas as $factura) {
		
		$oItem= new complex("Radicado_Factura","Id_Radicado_Factura", $factura['Id_Radicado_Factura']);
		$oItem->Estado_Radicado_Factura = $factura['Id_Tipo_Glosa'] != '' ? 'Glosada' : 'Radicada';
		$oItem->Id_Tipo_Glosa = $factura['Id_Tipo_Glosa'] != '' ? $factura['Id_Tipo_Glosa'] : '0';
		$oItem->Observacion_Glosa = $factura['Observacion_Glosa'] == '' ? ' ' : $factura['Observacion_Glosa'];
		$oItem->save();
	    unset($oItem);
	}

	//CERRAR RADICACION
	if ($cerrar_radicacion == 'si') {

		$query = '
			SELECT
				*
			FROM Radicado_Factura
			WHERE
				Id_Tipo_Glosa IS NOT NULL
				AND Id_Tipo_Glosa <> 0';
		
		$oCon= new Consulta();
		$oCon->setQuery($query);
		$oCon->setTipo('Multiple');
		$facturas_glosadas = $oCon->save();
		unset($oCon);

		if (count($facturas_glosadas) > 0) {
			$http_response->SetRespuesta(2, 'Alerta', 'No se puede cerrar la radicacion con factura glosadas!');
			$response = $http_response->GetRespuesta();
			echo json_encode($response);
			return;
		}
		
	}

	//SE CREA LA ACTIVIDAD DE ACTUALIZACION DE LAS FACTURAS
	$cadena_facturas = $util->ArrayToCommaSeparatedString($id_facturas);
	$detalle_actividad = 'Se editaron las facturas '.$cadena_facturas;

	GuardarActividadRadicado($modelo['Id_Radicado'], $modelo['Id_Funcionario'], $modelo['Codigo'], $detalle_actividad);
	$http_response->SetRespuesta(0, 'Actualizacion Exitosa', 'Se ha actualizado la radicacion exitosamente!');


	//CERRAR RADICACION
	if ($cerrar_radicacion == 'si') {
		
		$oItem= new complex("Radicado","Id_Radicado", $modelo['Id_Radicado']);
		$oItem->Estado = "Cerrada";
		$oItem->Fecha_Cierre = $fecha;
		$oItem->save();
		unset($oItem);

		GuardarActividadCierre($modelo['Id_Radicado'], $modelo['Id_Funcionario'], $modelo['Codigo']);
		$http_response->SetRespuesta(0, 'Cierre Exitoso', 'Se ha cerrado la radicacion exitosamente!');
	}

    
    $response = $http_response->GetRespuesta();

	echo json_encode($response);

	function GuardarActividadRadicado($idRadicado, $idFuncionario, $codigo, $detalle){
			
		$oItem= new complex("Actividad_Radicado","Id_Actividad_Radicado");
	    $oItem->Id_Funcionario = $idFuncionario;
	    $oItem->Id_Radicado = $idRadicado;
	    $oItem->Detalle = $detalle.', de la radicacion codigo '.$codigo;
	    $oItem->Estado = 'Edicion';
	    $oItem->save();
	    unset($oItem);
	}

	function GuardarActividadCierre($idRadicado, $idFuncionario, $codigo){
			
		$oItem= new complex("Actividad_Radicado","Id_Actividad_Radicado");
	    $oItem->Id_Funcionario = $idFuncionario;
	    $oItem->Id_Radicado = $idRadicado;
	    $oItem->Detalle = 'Se ha cerrado la radicacion codigo '.$codigo;
	    $oItem->Estado = 'Cerrado';
	    $oItem->save();
	    unset($oItem);
	}
?>