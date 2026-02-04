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

	if ($modelo['Id_Tipo_Activo_Fijo'] == '') {
		$oItem= new complex("Tipo_Activo_Fijo","Id_Tipo_Activo_Fijo");
	    $oItem->Nombre_Tipo_Activo = $modelo['Nombre_Tipo_Activo'];
	    $oItem->Categoria = $modelo['Categoria'];
	    $oItem->Vida_Util = $modelo['Vida_Util'];
	    $oItem->Vida_Util_PCGA = $modelo['Vida_Util_PCGA'];
	    $oItem->Porcentaje_Depreciacion_Anual = number_format($modelo['Porcentaje_Depreciacion_Anual'], 2);
	    $oItem->Porcentaje_Depreciacion_Anual_PCGA = number_format($modelo['Porcentaje_Depreciacion_Anual_PCGA'], 2);
	    $oItem->Id_Plan_Cuenta_Depreciacion_NIIF = $modelo['Id_Plan_Cuenta_Depreciacion_NIIF'];
	    $oItem->Id_Plan_Cuenta_Depreciacion_PCGA = $modelo['Id_Plan_Cuenta_Depreciacion_PCGA'];
	    $oItem->Id_Plan_Cuenta_NIIF = $modelo['Id_Plan_Cuenta_NIIF'];
	    $oItem->Id_Plan_Cuenta_PCGA = $modelo['Id_Plan_Cuenta_PCGA'];
	    $oItem->Id_Plan_Cuenta_Credito_Depreciacion_PCGA = $modelo['Id_Plan_Cuenta_Credito_Depreciacion_PCGA'];
	    $oItem->Id_Plan_Cuenta_Credito_Depreciacion_NIIF = $modelo['Id_Plan_Cuenta_Credito_Depreciacion_NIIF'];
	    $oItem->save();
	    unset($oItem);

	    $http_response->SetRespuesta(0, 'Registro Exitoso', 'Se ha registrado el tipo de activo fijo exitosamente!');
	    $repsonse = $http_response->GetRespuesta();
	}else{

		$oItem= new complex("Tipo_Activo_Fijo","Id_Tipo_Activo_Fijo", $modelo['Id_Tipo_Activo_Fijo']);	    
	    $oItem->Nombre_Tipo_Activo = $modelo['Nombre_Tipo_Activo'];
	    $oItem->Categoria = $modelo['Categoria'];
	    $oItem->Vida_Util = $modelo['Vida_Util'];
	    $oItem->Vida_Util_PCGA = $modelo['Vida_Util_PCGA'];
	    $oItem->Porcentaje_Depreciacion_Anual = number_format($modelo['Porcentaje_Depreciacion_Anual'], 2);
	    $oItem->Porcentaje_Depreciacion_Anual_PCGA = number_format($modelo['Porcentaje_Depreciacion_Anual_PCGA'], 2);
	    $oItem->Id_Plan_Cuenta_Depreciacion_NIIF = $modelo['Id_Plan_Cuenta_Depreciacion_NIIF'];
	    $oItem->Id_Plan_Cuenta_Depreciacion_PCGA = $modelo['Id_Plan_Cuenta_Depreciacion_PCGA'];
	    $oItem->Id_Plan_Cuenta_NIIF = $modelo['Id_Plan_Cuenta_NIIF'];
		$oItem->Id_Plan_Cuenta_PCGA = $modelo['Id_Plan_Cuenta_PCGA'];
		$oItem->Id_Plan_Cuenta_Credito_Depreciacion_PCGA = $modelo['Id_Plan_Cuenta_Credito_Depreciacion_PCGA'];
	    $oItem->Id_Plan_Cuenta_Credito_Depreciacion_NIIF = $modelo['Id_Plan_Cuenta_Credito_Depreciacion_NIIF'];
	    $oItem->save();
	    unset($oItem);

	    $http_response->SetRespuesta(0, 'Actualizacion Exitosa', 'Se ha actualizado el tipo de activo fijo exitosamente!');
	    $repsonse = $http_response->GetRespuesta();
	}

	echo json_encode($repsonse);
?>