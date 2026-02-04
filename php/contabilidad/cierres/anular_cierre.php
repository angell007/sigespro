<?php
ini_set('max_execution_time', 3600);
ini_set('memory_limit','510M');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');
include_once('../../../class/class.contabilizar.php');

$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;
$response = [];

if ($id) {

    $contabilidad = new Contabilizar();

    $oItem = new complex('Cierre_Contable','Id_Cierre_Contable',$id);
    $oItem->Estado = 'Anulado';
    $oItem->save();
    unset($oItem);

    $contabilidad->AnularMovimientoContable($id, 33);

    $response['mensaje'] = "Cierre anulado satisfactoriamente.";
    $response['titulo'] = "Exito!";
    $response['codigo'] = "success";
} else {
    $response['mensaje'] = "Ha ocurrido un error inesperado al procesar la información.";
    $response['titulo'] = "Ooops!";
    $response['codigo'] = "error";
}

echo json_encode($response);

?>