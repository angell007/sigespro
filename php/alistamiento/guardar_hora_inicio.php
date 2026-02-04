<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
date_default_timezone_set('America/Bogota');
require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$funcionario=( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );
$tipo=( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : '' );
$mod = ( isset( $_REQUEST['mod'] ) ? $_REQUEST['mod'] : '' );



//remision - devolucion_compra
$id_mod = 'Id_'.$mod;

$oItem = new complex($mod,$id_mod,$id);
$remision = $oItem->getData();
unset($oItem);



$oItem = new complex($mod,$id_mod,$id);
if($tipo=="Fase1"){
    $oItem->Fase_1=$funcionario;
    $oItem->Inicio_Fase1=date("Y-m-d H:i:s");
}elseif($tipo=="Fase2"){
    $oItem->Fase_2=$funcionario;
    $oItem->Inicio_Fase2=date("Y-m-d H:i:s");
}
$oItem->save();
unset($oItem);

$resultado="Echo";

echo json_encode($resultado);

?>	




