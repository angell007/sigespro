<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id_inventario_fisico = ( isset( $_REQUEST['Id_Inventario_Fisico_Punto'] ) ? $_REQUEST['Id_Inventario_Fisico_Punto'] : '' );

$productos = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );

$oItem = new complex('Inventario_Fisico_Punto', 'Id_Inventario_Fisico_Punto', $id_inventario_fisico);
$oItem->Lista_Productos = $productos;
$band = $oItem->Id_Inventario_Fisico_Punto;
$oItem->save();
unset($oItem);

if($band){
    $resultado['titulo'] = "Operación Exitosa";
    $resultado['mensaje'] = "Se ha ajustado parcialmente el inventario";
    $resultado['tipo'] = "success";
}else{
    $resultado['titulo'] = "Error";
    $resultado['mensaje'] = "Ha ocurrido un error inesperado. Por favor verifique su conexión a internet.";
    $resultado['tipo'] = "error";
}

echo json_encode($resultado);
?>