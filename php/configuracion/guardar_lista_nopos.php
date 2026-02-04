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

// var_dump( $datos);exit;
$oItem = new complex("Lista_Producto_Nopos","Id_Lista_Producto_Nopos");
$oItem->Nombre=$datos["Nombre"];
$oItem->Id_Cliente=$datos["Id_Cliente"];
$oItem->save();
unset($oItem);

$resultado['mensaje'] = "¡Lista Guardada Exitosamente!";
$resultado['type'] = "success";
$resultado['text'] = "Lista Guardada";

echo json_encode($resultado);