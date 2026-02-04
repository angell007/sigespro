<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
date_default_timezone_set('America/Bogota');
require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );
$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$funcionario=( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );

$id_mod = 'Id_'.$mod;

$oItem = new complex($mod,$id_mod,$id);
$oItem->Estado_Alistamiento=1;
$oItem->Fin_Fase1=date("Y-m-d H:i:s");
$oItem->save();
unset($oItem);

$oItem = new complex($mod,$id_mod,$id);
$remision = $oItem->getData();
unset($oItem);

//Guardar actividad de la remision 
$oItem = new complex('Actividad_'.$mod,"Id_Actividad_".$mod);
$oItem->$id_mod=$id;
$oItem->Identificacion_Funcionario=$funcionario;
$oItem->Detalles="Se realizo la Fase 1 de Alistamiento de la Remision ".$remision["Codigo"];
$oItem->Estado="Fase 1";
$oItem->Fecha=date("Y-m-d H:i:s");
$oItem->save();
unset($oItem);

$resultado['mensaje'] = "Se ha guardado correctamente la Fase 1 de la Remision con codigo: ". $remision['Codigo'];
$resultado['tipo'] = "success";
echo json_encode($resultado);




