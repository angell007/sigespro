<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.complex.php');
	include_once('../../class/class.configuracion.php');
	include_once('../../class/class.http_response.php');
	include_once('../../class/class.consulta.php');

	$http_response = new HttpResponse();
	$response = array();

	$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '');
    $tipo = ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : '');
    
    $modelo = json_decode($modelo, true); 
    

    $oItem=new Complex($modelo['Tabla'],'Id_'.$modelo['Tabla'],$modelo['Id']);
    $oItem->Porcentaje=number_format($modelo['Porcentaje'],6,".","");
    $oItem->save();
    unset($oItem);

    $http_response->SetRespuesta(0, 'Registro Exitoso', 'Se ha(n) agregado(s) exitosamente los datos de las dispensaciones pendientes!');
    $response = $http_response->GetRespuesta();

	echo json_encode($response);


	

	
?>