<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
date_default_timezone_set('America/Bogota');
require_once '../../config/start.inc.php';
include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';
require_once '../../class/class.configuracion.php';
require_once '../../class/class.qr.php'; /* AGREGAR ESTA CLASE PARA GENERAR QR */
include_once '../../class/class.consulta.php';
$configuracion = new Configuracion();

$mod = (isset($_REQUEST['modulo']) ? $_REQUEST['modulo'] : '');
$datos = (isset($_REQUEST['datos']) ? $_REQUEST['datos'] : '');
$productos = (isset($_REQUEST['productos']) ? $_REQUEST['productos'] : '');
$id_pre_compra = (isset($_REQUEST['id_pre_compra']) ? $_REQUEST['id_pre_compra'] : '');

$datos = (array) json_decode($datos);
$productos = (array) json_decode($productos, true);

if (isset($datos['Id_Orden_Compra_Nacional']) && $datos['Id_Orden_Compra_Nacional'] != "") {
    $oItem = new complex('Producto_Orden_Compra_Nacional',"Id_Orden_Compra_Nacional",$datos['Id_Orden_Compra_Nacional']);
    $oItem->delete();
    unset($oItem);

    $oItem = new complex($mod, "Id_" . $mod, $datos['Id_Orden_Compra_Nacional']);

} else {

    $cod = $configuracion->getConsecutivo($mod, 'Orden_Compra');

    $datos['Codigo'] = $cod;

    $oItem = new complex($mod, "Id_" . $mod);

}

$i = -1;
foreach ($datos as $index => $value) {$i++;
    $datos[$i]['Identificacion_Funcionario'] = $funcionario;
    $oItem->$index = $value;
}

if ($mod == 'Orden_Compra_Nacional' && $id_pre_compra) {
    #asignar la precompra
    $oItem->Id_Pre_Compra = $id_pre_compra;
}
$oItem->save();
$id_venta = $oItem->getId();
$resultado = array();
unset($oItem);

if ($mod == 'Orden_Compra_Nacional' && $id_pre_compra) {
    #asignar la precompra
    $oItem = new complex('Pre_Compra', "Id_Pre_Compra", $id_pre_compra);
    $oItem->Id_Orden_Compra_Nacional = $id_venta;
    $oItem->save();
    unset($oItem);
}
/* AQUI GENERA QR */
//$qr = generarqr('ordencompranacional',$id_venta,$MY_FILE.'/IMAGENES/QR/');
$qr = generarqr('ordencompranacional', $id_venta, 'IMAGENES/QR/');
$oItem = new complex("Orden_Compra_Nacional", "Id_Orden_Compra_Nacional", $id_venta);
$oItem->Codigo_Qr = $qr;
$oItem->save();
unset($oItem);
/*HASTA AQUI GENERA QR */

unset($productos[count($productos) - 1]);

foreach ($productos as $producto) {

    $oItem = new complex('Producto_Orden_Compra_Nacional', "Id_Producto_Orden_Compra_Nacional");

    $producto["Id_Orden_Compra_Nacional"] = $id_venta;
    foreach ($producto as $index => $value) {
            $oItem->$index = $value;
    }
    $oItem->Costo = number_format($producto['Costo'], 2, '.', '');

    $oItem->Iva = $producto['Iva'] == '' ? '0' : number_format($producto['Iva'], 0, '', '');
    $oItem->save();
    unset($oItem);
}
if($datos['Id_Orden_Compra_Nacional']){

    $oItem = new complex('Actividad_Orden_Compra', "Id_Actividad_Orden_Compra");
    $oItem->Id_Orden_Compra_Nacional = $id_venta;
    $oItem->Identificacion_Funcionario = $datos["Identificacion_Funcionario"]!==''? $datos["Identificacion_Funcionario"] : 0;
    $oItem->Detalles = "Se edito la orden de compra con codigo " . $datos['Codigo'];
    $oItem->Fecha = date("Y-m-d H:i:s");
    $oItem->Estado = "Edicion";
    $oItem->save();
    unset($oItem);

} else {
    $oItem = new complex('Actividad_Orden_Compra', "Id_Actividad_Orden_Compra");
    $oItem->Id_Orden_Compra_Nacional = $id_venta;
    $oItem->Identificacion_Funcionario = $datos["Identificacion_Funcionario"];
    $oItem->Detalles = "Se creo la orden de compra con codigo " . $datos['Codigo'];
    $oItem->Fecha = date("Y-m-d H:i:s");
    $oItem->Estado = "Creacion";
    $oItem->save();
    unset($oItem);

}

if ($id_venta != "") {
    $resultado['mensaje'] = "Se ha guardado correctamente la orden de compra: " . $datos['Codigo'];
    $resultado['tipo'] = "success";
} else {
    $resultado['mensaje'] = "ha ocurrido un error guardando la informacion, por favor verifique";
    $resultado['tipo'] = "error";
}

echo json_encode($resultado);

?>