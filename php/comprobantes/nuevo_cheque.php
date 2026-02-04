<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$datos = isset($_REQUEST['datos']) ? $_REQUEST['datos'] : '';

$datos = (array) json_decode($datos, true);

$oItem = new complex('Cheque_Consecutivo','Id_Cheque_Consecutivo');

if ($datos['Inicial'] != 1) {
    $datos['Consecutivo'] = $datos['Inicial'];
}

foreach ($datos as $index => $value) {
    $oItem->$index = $value;
}
$oItem->save();
$cheque = $oItem->getId();

if ($cheque) {
    $resultados['mensaje'] = "Se ha registrado un nuevo para el banco seleccionado.";
    $resultados['titulo'] = "Operación Exitosa";
    $resultados['tipo'] = "success";
} else {
    $resultados['mensaje'] = "Ha ocurrido un error inesperado, por favor verifique su conexión.";
    $resultados['titulo'] = "Error";
    $resultados['tipo'] = "error";
}

echo json_encode($resultados);

?>