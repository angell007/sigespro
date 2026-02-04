<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');


$estado = isset($_REQUEST['Estado']) && $_REQUEST['Estado'] != '' ? $_REQUEST['Estado'] : '';
$observacion = isset($_REQUEST['Observacion']) && $_REQUEST['Observacion'] != '' ? $_REQUEST['Observacion'] : '';
$productos = isset($_REQUEST['Productos']) && $_REQUEST['Productos'] != '' ? $_REQUEST['Productos'] : '';

$productos = (array) json_decode($productos, true);


foreach ($productos as $pend) {
	$oItem = new complex('Producto_Orden_Pedido', 'Id_Producto_Orden_Pedido', $pend['Id_Producto_Orden_Pedido']);
	$oItem->Estado = $estado;
	$oItem->Observacion = $observacion;
	$oItem->Fecha_Cancelado = date('Y-m-d H:i:s');
	$oItem->save();
}

$respuesta = array('titulo' => 'Exito!', 'mensaje' => 'Se ha cambiado el estado de los pendientes', 'tipo' => 'success');

echo json_encode($respuesta);


exit;
