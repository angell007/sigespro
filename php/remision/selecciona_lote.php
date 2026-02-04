<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

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


$resultado["Respuesta"]="ok";

echo json_encode($resultado);

?>