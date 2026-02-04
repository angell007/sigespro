<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
date_default_timezone_set('America/Bogota');
require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$id = ( isset( $_REQUEST['id_pre_compra'] ) ? $_REQUEST['id_pre_compra'] : '' );

$oItem = new complex('Pre_Compra','Id_Pre_Compra',$id);
$oItem->Estado="Solicitada";
$oItem->save();
unset($oItem);


$resultado=[];
echo json_encode($resultado);

?>	