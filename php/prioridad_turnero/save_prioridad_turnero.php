<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.http_response.php');
require_once('../../class/class.configuracion.php');
require_once('../../class/class.qr.php');

$queryObj = new QueryBaseDatos();
$response = array();
$http_response = new HttpResponse();

date_default_timezone_set('America/Bogota');


$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );
$tipo = ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : '' );
$modelo = (array) json_decode(utf8_decode($modelo));

if($tipo=='Guardar'){
    if($modelo['Id_Prioridad_Turnero']!=''){
        $oItem=new complex('Prioridad_Turnero','Id_Prioridad_Turnero',$modelo['Id_Prioridad_Turnero']);
    }else{
        $oItem=new complex('Prioridad_Turnero','Id_Prioridad_Turnero');
    }
    foreach ($modelo as $key => $value) {
        $oItem->$key=$value;
    }
    $oItem->save();
    unset($oItem);
    
    
    
    $tipo= isset($modelo['Id_Prioridad_Turnero']) ? 'actualizado' : ' creado'; 
    
    $http_response->SetRespuesta(0, 'Guardado Correctamente', 'Se ha '.$tipo.' los datos correctamente! ');
    $response = $http_response->GetRespuesta();
}else{
    $oItem=new complex('Prioridad_Turnero','Id_Prioridad_Turnero',$modelo['Id_Prioridad_Turnero']);
    $oItem->delete();
    unset($oItem);
    $http_response->SetRespuesta(0, 'Eliminado Correctamente', 'Se ha eliminado correctamente el registro! ');
    $response = $http_response->GetRespuesta();
}


echo json_encode($response);

 ?>





