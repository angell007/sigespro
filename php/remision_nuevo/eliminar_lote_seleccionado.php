<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.http_response.php');
require_once('../../class/class.configuracion.php');

$queryObj = new QueryBaseDatos();
$response = array();
$http_response = new HttpResponse();

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$datos = (array)json_decode($datos, true);

foreach($datos as $dato){

    if((int)$dato["Id_Inventario_Nuevo"]!=0){
        $oItem = new complex("Inventario_Nuevo","Id_Inventario_Nuevo",(int)$dato["Id_Inventario_Nuevo"]);
        $actual = $oItem->getData();
        
        $act = (int)$actual["Cantidad_Seleccionada"];
        $num = (int)$dato["Cantidad"];
        $fin = $act - $num;
        if($fin<0){
            $fin=0;
        }
        $oItem->Cantidad_Seleccionada =  (int)$fin;
        $oItem->save();
        unset($oItem);
    }
   
}

$http_response->SetRespuesta(0, 'Operacion exitosa', 'Se ha borrado la cantidad seleccionada!');
$response = $http_response->GetRespuesta();

echo json_encode($response);




?>