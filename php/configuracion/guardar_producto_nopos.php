<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );

$datos = (array) json_decode($datos);

if($datos['Id_Producto_NoPos']!=''){
    $oItem = new complex("Producto_NoPos","Id_Producto_NoPos",$datos['Id_Producto_NoPos'] );
    $oItem->Precio_Anterior = $oItem->Precio;
    
}else{
    $oItem = new complex("Producto_NoPos","Id_Producto_NoPos");

}

$oItem->Id_Lista_Producto_Nopos=$datos["Id_Lista_Producto_Nopos"];
$oItem->Cum=$datos["Cum"];
$oItem->Precio=$datos["Precio"];
$oItem->Cum_Homologo=$datos["Cum_Homologo"];
$oItem->Precio_Homologo=$datos["Precio_Homologo"];
$oItem->save();
unset($oItem);

$resultado['mensaje']="Se ha agregado correctamente el producto NoPos!";
$resultado['tipo']="success";

echo json_encode($resultado);
?>