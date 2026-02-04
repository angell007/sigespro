<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );

$datos = (array) json_decode($datos);

$oItem = new complex("Tipo_Rechazo","Id_Tipo_Rechazo");
$oItem->Nombre=strtoupper($datos["Nombre"]);
$oItem->save();
unset($oItem);



$resultado['mensaje']="Tipo de Rechazo creada Correctamente!";
$resultado['tipo']="success";

echo json_encode($resultado);
?>