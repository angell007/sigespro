<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id_inventario_fisico_puntos = 25;

$oItem = new complex('Inventario_Fisico_Punto', 'Id_Inventario_Fisico_Punto', $id_inventario_fisico_puntos);
$inv=$oItem->getData();
unset($oItem);

$productos = (array) json_decode($inv["Lista_Productos"], true);
// var_dump($productos);
// exit;

$query = "SELECT Id_Producto_Inventario_Fisico, Id_Producto, Lote FROM Producto_Inventario_Fisico_Punto WHERE Id_Inventario_Fisico_Punto=12";

$con = new consulta();
$con->setQuery($query);
$con->setTipo("Multiple");
$resultado = $con->getData();
unset($con);

foreach ($productos as $prod) {
    unset($prod['Lotes'][count($prod['Lotes'])-1]);

    if (count($prod['Lotes']) > 0) {
        foreach ($prod['Lotes'] as $item) {
            foreach ($resultado as $value) {
                if ($item['Lote'] == $value['Lote'] && $prod['Id_Producto'] == $value['Id_Producto']) {
                    $oItem = new complex('Producto_Inventario_Fisico_Punto', 'Id_Producto_Inventario_Fisico', $value['Id_Producto_Inventario_Fisico']);
                    $oItem->Fecha_Segundo_Conteo = date('Y-m-d');
                    $oItem->Segundo_Conteo = $item['Cantidad_Encontrada'];
                    $oItem->save();
                    unset($oItem);
                }
            }
        }
    }
}

// Cambiar el estado del inventario fisico
$oItem = new complex('Inventario_Fisico_Punto', 'Id_Inventario_Fisico_Punto', $id_inventario_fisico_puntos);
$oItem->Estado = 'Por Confirmar';
$band = $oItem->Id_Inventario_Fisico;
$oItem->save();
unset($oItem);

echo "Listo";
?>