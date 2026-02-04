<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$oItem = new complex('Turnero','Id_Auditoria', $id);
$oItem->Fecha = date('Y-m-d');
$oItem->Hora_Turno = "23:59:59";
$oItem->Estado = "Espera";
$id_turnero = $oItem->Id_Turnero;
$oItem->save();
unset($oItem);

if ($id_turnero) {
    $resultado['mensaje'] = "Reactivado Exitosamente!";
    $resultado['tipo'] = "success";
} else {
    $resultado['mensaje'] = "Ha ocurrido un error de conexión. Por favor intentelo de nuevo!";
    $resultado['tipo'] = "error";
}

echo json_encode($resultado);

?>