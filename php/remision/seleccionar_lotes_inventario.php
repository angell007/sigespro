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


$oItem = new complex("Inventario","Id_Inventario",$datos["Id_Inventario"]);
$cantidad_seleccionada=($oItem->Cantidad_Seleccionada-$datos["Cantidad_Seleccionada"])+$datos["Cantidad"];
if($cantidad_seleccionada<0){
    $cantidad_seleccionada=0;
}
$oItem->Cantidad_Seleccionada = number_format($cantidad_seleccionada,0,"","");
$oItem->save();
unset($oItem);


$http_response->SetRespuesta(0, 'Registro Exitoso', 'Se ha guardado la cantidad seleccionada!');
$response = $http_response->GetRespuesta();

echo json_encode($response);



?>