<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once('../../class/class.configuracion.php');
require_once('../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */


$datos = isset($_REQUEST['datos']) ? $_REQUEST['datos'] : false;
$id_factura = isset($_REQUEST['Id_Factura']) ? $_REQUEST['Id_Factura'] : false;

$datos = (array) json_decode($datos, true);

$oItem = new complex('Factura','Id_Factura',$id_factura);

foreach ($datos as $index => $valor) {
    $oItem->$index = $valor;
}
$id_factura = $oItem->Id_Factura;
$oItem->save();
unset($oItem);


if ($id_factura) {
    $resultado['title'] = "Exito!";
    $resultado['mensaje'] = "Información agregada satisfactoriamente.";
    $resultado['tipo'] = "success";
} else {
    $resultado['title'] = "Error";
    $resultado['mensaje'] = "Ha ocurrido un error durante el proceso, si el error persiste comunicate con soporte tecnico.";
    $resultado['tipo'] = "error"; 
}

echo json_encode($resultado);

?>		