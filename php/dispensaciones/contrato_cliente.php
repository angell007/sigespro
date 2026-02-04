<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$oItem = new complex("Contrato","Id_Cliente",$id);
$detalle= $oItem->getData();
unset($oItem);

echo json_encode($detalle);
?>