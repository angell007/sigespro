<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
date_default_timezone_set('America/Bogota');
require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );

$datos = (array) json_decode($datos);

$oItem = new complex("Cuenta_Banco","Id_Cuenta_Banco");
$oItem->Id_Plan_Cuenta=$datos["Id_Plan_Cuenta"];
$oItem->Nombre=$datos["Nombre"];
$oItem->Numero_Cuenta=$datos["Numero_Cuenta"];
$oItem->Saldo=$datos["Saldo"];
$oItem->Descripcion=$datos["Descripcion"];
$oItem->Fecha=date("Y-m-d H:i:s");
$oItem->Tipo=$datos["Tipo"];
$oItem->save();
unset($oItem);



$resultado['mensaje']="Cuenta Banco creado Correctamente!";
$resultado['tipo']="success";

echo json_encode($resultado);
?>