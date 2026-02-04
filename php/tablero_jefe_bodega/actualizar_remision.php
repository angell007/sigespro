<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once('../../class/class.configuracion.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['Id'] ) ? $_REQUEST['Id'] : '' );
$tipo=( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : '' );

$oItem = new complex('Remision',"Id_Remision",$id );
if($tipo=='fase1'){
    $oItem->Fase_1="0";
}else if ($tipo=='fase2'){
    $oItem->Fase_2="0";
}
$oItem->save();
unset($oItem);

$resultado['mensaje'] = "Se ha liberado la Remision Correctamente ";
$resultado['tipo'] = "success";


echo json_encode($resultado);

?>	