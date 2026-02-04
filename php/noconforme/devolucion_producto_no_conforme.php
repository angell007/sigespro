<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once('../../class/class.configuracion.php');
include_once('../../class/class.consulta.php');
require_once('../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */

$configuracion = new Configuracion();

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$productos = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );
$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$datos = (array) json_decode($datos);
$productos = (array) json_decode($productos , true);

$cod = $configuracion->getConsecutivo('Devolucion_Compra','Devolucion_Compras');
$datos['Codigo'] = $cod;

$oItem = new complex('Devolucion_Compra','Id_Devolucion_Compra');
foreach($datos as $index=>$value){
   $oItem->$index=$value;
}
$oItem->save();
$idDevolucionCompra = $oItem->getId();
unset($oItem);

/* AQUI GENERA QR */
$qr = generarqr('devolucioncompra',$idDevolucionCompra,'/IMAGENES/QR/');
$oItem = new complex("Devolucion_Compra","Id_Devolucion_Compra",$idDevolucionCompra);
$oItem->Codigo_Qr=$qr;
$oItem->save();
unset($oItem);
/* HASTA AQUI GENERA QR */

foreach($productos as $producto){
    $oItem = new complex('Producto_Devolucion_Compra',"Id_Producto_Devolucion_Compra");
    $oItem->Id_Devolucion_Compra = $idDevolucionCompra;
    $oItem->Id_Producto = $producto['Id_Producto'];
    $oItem->Cantidad = $producto['Cantidad'];
    $oItem->Motivo = $producto['Motivo'];
    $oItem->save();
    unset($oItem);
}

if ($id != '') {
	$oItem = new complex('No_Conforme', 'Id_No_Conforme', $id);
	$oItem->Estado = 'Cerrado';
	$oItem->save();
	unset($oItem);
}


    
$resultado['mensaje'] = "Se ha generado la devolucion de compra con codigo: ". $datos['Codigo'];
$resultado['tipo'] = "success";


echo json_encode($resultado);


?>