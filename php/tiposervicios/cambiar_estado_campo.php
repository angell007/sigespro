<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.http_response.php');
	include_once('../../class/class.table.php');

/* 	$oTabla=new table('Dispensacion');
	//$oTabla->setName('Dispensacion');
	$oTabla->addColumn('Nombre_Prueba','varchar(100)');
	$oTabla->save();
	unset($oTabla); */

	 $http_response = new HttpResponse();
	$queryObj = new QueryBaseDatos();
	$repsonse = array();

	$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );
	$tipo = ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : '' );

	$modelo = json_decode($modelo, true);

	if($tipo=='Estado'){
		$oItem= new complex("Campos_Tipo_Servicio","Id_Campos_Tipo_Servicio", $modelo['Id_Campos_Tipo_Servicio']);
		$oItem->Estado=$modelo['Estado'];
		$oItem->save();
		unset($oItem);
			
	
		$http_response->SetRespuesta(0, 'Cambio exitoso', "Se ha $modelo[Estado]  el  campo exitosamente!");
		$repsonse = $http_response->GetRespuesta();
	}else{
		$oItem= new complex("Campos_Tipo_Servicio","Id_Campos_Tipo_Servicio", $modelo['Id_Campos_Tipo_Servicio']);
		$oItem->Longitud=$modelo['Longitud'];
		$oItem->save();
		unset($oItem);
			
	
		$http_response->SetRespuesta(0, 'Cambio exitoso', "Se ha la longitud del  campo exitosamente!");
		$repsonse = $http_response->GetRespuesta();
	}


	echo json_encode($repsonse); 


?>