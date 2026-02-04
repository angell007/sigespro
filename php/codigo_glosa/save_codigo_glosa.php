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
	require('../../class/class.guardar_archivos.php');



	$http_response = new HttpResponse();
	$response = array();

	$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '');
    $tabla = ( isset( $_REQUEST['tabla'] ) ? $_REQUEST['tabla'] : '');
    
    $modelo = json_decode(utf8_decode($modelo), true);
    
    if($modelo['Id_'.$tabla]!='' && $modelo['Id_'.$tabla]!=null ){
        $oItem=new complex($tabla,'Id_'.$tabla,$modelo['Id_'.$tabla]);
        unset($modelo['Id_'.$tabla]);
    }else{
        $oItem=new complex($tabla,'Id_'.$tabla);
    }
    foreach ($modelo as $index => $value) {
        if($value!=''){
            $oItem->$index=$value;
        }                    
    }

    $oItem->save();
    unset($oItem);

    $http_response->SetRespuesta(0, 'Registro Exitoso', 'Se ha(n) agregado(s) exitosamente los datos!');
    $response = $http_response->GetRespuesta();

	echo json_encode($response);


	

	
?>