<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once('../../class/class.configuracion.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.contabilizar.php');
require_once('../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */

$configuracion = new Configuracion();
$contabilizacion = new Contabilizar();

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$productos = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );

$datos = (array) json_decode($datos);
$productos = (array) json_decode($productos , true);

/* var_dump($datos);
var_dump($productos);
exit;
 */
// $cod = $configuracion->Consecutivo('Devolucion_Compras');
$datos['Codigo'] = 'TEST';

$oItem = new complex('Devolucion_Compra','Id_Devolucion_Compra');
foreach($datos as $index=>$value){
   $oItem->$index=$value;
}
// $oItem->save();
$idDevolucionCompra = $oItem->getId();
unset($oItem);

/* AQUI GENERA QR */
$qr = generarqr('devolucioncompra',$idDevolucionCompra,'/IMAGENES/QR/');
$oItem = new complex("Devolucion_Compra","Id_Devolucion_Compra",$idDevolucionCompra);
$oItem->Codigo_Qr=$qr;
// $oItem->save();
unset($oItem);
/* HASTA AQUI GENERA QR */

unset($productos[count($productos)-1]);

foreach($productos as $producto){
    $oItem = new complex('Producto_Devolucion_Compra',"Id_Producto_Devolucion_Compra");
    $oItem->Id_Devolucion_Compra = $idDevolucionCompra;
    $oItem->Id_Producto = $producto['Id_Producto'];
    $oItem->Cantidad = $producto['Cantidad'];
    $oItem->Lote = $producto['Lote'];
    $oItem->Fecha_Vencimiento = $producto['Fecha_Vencimiento'];
    $oItem->Id_Inventario = $producto['Id_Inventario'];
    $oItem->Motivo = $producto['Motivo'];
    $oItem->Costo = $producto['Costo'];
    $oItem->Impuesto = number_format($producto['Impuesto'],0,"","");
    // $oItem->save();
    unset($oItem);

    $oItem = new complex('Inventario','Id_Inventario', $producto['Id_Inventario']);
    $cantidad_final = $oItem->Cantidad - number_format($producto['Cantidad'],0,"","");
    $oItem->Cantidad = number_format($cantidad_final,0,"","");
    // $oItem->save();
    unset($oItem);
}

    
$resultado['mensaje'] = "Se ha generado la devolucion de compra con codigo: ". $datos['Codigo'];
$resultado['tipo'] = "success";


// echo json_encode($resultado);

$datos_movimientos['Id_Registro'] = 111111;
$datos_movimientos['datos'] = $datos;
$datos_movimientos['productos'] = $productos;

$contabilizacion->CrearMovimientoContable('Devolucion Acta', $datos_movimientos);


?>