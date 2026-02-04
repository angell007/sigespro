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
$datos = (array) json_decode($datos);

if ($datos['Id_Inventario_Contrato']) {
    $oItem = new complex("Inventario_Contrato","Id_Inventario_Contrato",$datos["Id_Inventario_Contrato"]);
                $cantidad_seleccionada=($oItem->Cantidad_Seleccionada-$datos["Cantidad_Seleccionada"])+$datos["Cantidad"];
                if($cantidad_seleccionada<0){
                    $cantidad_seleccionada=0;
                }
}else{
    $oItem = new complex("Inventario_Nuevo","Id_Inventario_Nuevo",$datos["Id_Inventario_Nuevo"]);
            $cantidad_seleccionada=($oItem->Cantidad_Seleccionada-$datos["Cantidad_Seleccionada"])+$datos["Cantidad"];
            if($cantidad_seleccionada<0){
                $cantidad_seleccionada=0;
            }
}

$oItem->Cantidad_Seleccionada = number_format($cantidad_seleccionada,0,"","");
$oItem->save();
unset($oItem);

$http_response->SetRespuesta(0, 'Registro Exitoso', 'Se ha guardado la cantidad seleccionada!');
$response = $http_response->GetRespuesta();

echo json_encode($response);



?>