<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );
$datos = ( isset( $_REQUEST['mensaje'] ) ? $_REQUEST['mensaje'] : '' );

$datos = (array) json_decode($datos);

if(isset($datos["id"])&&$datos["id"] != ""){
	$oItem = new complex($mod,"Id_".$mod,$datos["id"]);
}else{
	$oItem = new complex($mod,"Id_".$mod);
}

foreach($datos as $index=>$value) {
    $oItem->$index=$value;
}

$oItem->save();
unset($oItem);

$oLista = new lista($mod);
$lista= $oLista->getlist();
unset($oLista);

echo json_encode($lista);
?>