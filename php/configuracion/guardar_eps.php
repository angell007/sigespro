<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );
$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );

$datos = (array) json_decode($datos);

$oItem = new complex($mod,"Id_".$mod);
$oItem->Nombre=strtoupper($datos["Nombre"]);
$oItem->Codigo_Eps=strtoupper($datos["Codigo_Eps"]);
$oItem->Nit=$datos["Nit"];


$oItem->save();
unset($oItem);

$oLista = new lista($mod);
$lista= $oLista->getlist();
unset($oLista);

echo json_encode($lista);
?>