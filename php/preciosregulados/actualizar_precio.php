<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
date_default_timezone_set('America/Bogota');
require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once('../../class/class.configuracion.php');
include_once('../../class/class.consulta.php');
require_once('./helper_lista_precio_ganancia/funciones_lista_precio_ganancia.php');


$productos = (isset($_REQUEST['datos']) ? $_REQUEST['datos'] : '');
$funcionario = isset($_REQUEST['funcionario']) ? $_REQUEST['funcionario'] : false;

$productos =  json_decode($productos, true);


guardarActividadRegulado($productos['Id_Precio_Regulado'], $funcionario, $productos['Precio'], $productos['PrecioNuevo'], 'Actualizacion de precio');


$oItem = new complex('Precio_Regulado', "Id_Precio_Regulado", $productos['Id_Precio_Regulado']);
$oItem->Precio_Anterior = $oItem->Precio;
$oItem->Precio_Venta_Anterior = $oItem->Precio_Venta;
$oItem->Precio = $productos['Precio'];
$oItem->Precio_Venta = $productos['Precio_Venta'];
$oItem->save();
unset($oItem);

$resultado['mensaje'] = "Se ha Guardado Correctamente!";
$resultado['tipo'] = "success";

echo json_encode($resultado);
