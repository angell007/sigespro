<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
date_default_timezone_set('America/Bogota');
require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );;

$datos = (array) json_decode($datos , true);

$fecha = date('Y-m-d H:i:s');

$oItem = new complex("Novedad","Id_Novedad",$datos['Id_Novedad']);
$oItem->Estado=$datos['Estado'];
$oItem->Fecha_Aprobacion=$fecha;
$oItem->Funcionario_Aprueba=$datos['Funcionario_Aprueba'];
$oItem->save();
unset($oItem);

$oItem = new complex("Alerta","Id_Alerta");
$oItem->Identificacion_Funcionario=$datos['Funcionario_Reporta'];
$oItem->Tipo="Permiso";
$oItem->Fecha=$fecha;
$oItem->Detalles="El permiso de  ".$datos['Fecha_Reporte']." ha sido ".$datos['Estado'];
$oItem->save();
unset($oItem);



$resultado['mensaje'] = "¡Permiso actualizado Correctamente!";
$resultado['tipo'] = "success";

echo json_encode($resultado);

?>