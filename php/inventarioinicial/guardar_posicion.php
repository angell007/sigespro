<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );
$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );

$productos = (array) json_decode($datos , true);
foreach ($productos as $lote ) {
    $oItem = new complex('Inventario_Inicial',"Id_Inventario_Inicial",$lote['Id_Inventario_Inicial']);
    $oItem->Estiba=$lote["Estiba"];
    $oItem->Fila=$lote["Fila"];
    $oItem->save();
    unset($oItem);
}
$resultado=[];
echo json_encode($resultado);
?>