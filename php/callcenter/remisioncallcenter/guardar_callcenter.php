<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );
$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );

$datos = (array) json_decode($datos);

if(isset($datos["id"])&&$datos["id"] != ""){
	$oItem = new complex($mod,"Id_".$mod,$datos["id"]);
}else{
	$oItem = new complex($mod,"Id_".$mod);
}

unset($datos["Id_Remision_Callcenter"]);

if($datos["Fecha_Prox_Llamada"]==''){
	unset($datos["Fecha_Prox_Llamada"]);
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