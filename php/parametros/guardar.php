<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$modulo = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );

$datos = (array) json_decode($datos);

$oItem = new complex($modulo,"Id_".$modulo);
$oItem->Nombre=strtoupper($datos["Nombre"]);
$oItem->Codigo=$datos["Codigo"];
if($datos["Nit"]){
    $oItem->Nit=$datos["Nit"];
}
$oItem->save();
unset($oItem);



$resultado['mensaje']="Se ha creada el registro Correctamente!";
$resultado['tipo']="success";

echo json_encode($resultado);
?>