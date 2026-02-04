<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id_inventario_fisico = 13;

$oItem = new complex('Inventario_Fisico', 'Id_Inventario_Fisico', $id_inventario_fisico);
$inv=$oItem->getData();
unset($oItem);

$productos = (array) json_decode($inv["Lista_Productos"], true);
//var_dump($productos);
//exit;

foreach ($productos as $prod) {
    unset($prod['Lotes'][count($prod['Lotes'])-1]);

    foreach ($prod['Lotes'] as $item) {
        $oItem = new complex('Producto_Inventario_Fisico', 'Id_Producto_Inventario_Fisico');
        $oItem->Id_Producto = $prod['Id_Producto'];
        $oItem->Id_Inventario = $item['Id_Inventario'];
        $oItem->Primer_Conteo = $item['Cantidad_Encontrada'];
        $oItem->Fecha_Primer_Conteo = date('Y-m-d');
        $oItem->Cantidad_Inventario = $item['Cantidad'];
        $oItem->Id_Inventario_Fisico = $id_inventario_fisico;
        $oItem->Lote = $item['Lote'];
        $oItem->Fecha_Vencimiento = $item['Fecha_Vencimiento'];
        $oItem->save();
        unset($oItem);
    }
}

// Cambiar el estado del inventario fisico
$oItem = new complex('Inventario_Fisico', 'Id_Inventario_Fisico', $id_inventario_fisico);
$oItem->Estado = 'Por Confirmar';
$band = $oItem->Id_Inventario_Fisico;
$oItem->save();
unset($oItem);

echo "Listo";
?>