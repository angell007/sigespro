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
$oItem->Cantidad_Seleccionada = ($oItem->Cantidad_Seleccionada-$datos["Cantidad_Seleccionada"])+$datos["Cantidad"];
//$oItem->save();
unset($oItem);


$resultado["Respuesta"]="ok";

echo json_encode($resultado);

?>