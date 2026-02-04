<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once '../../config/start.inc.php';
include_once '../../class/class.complex.php';

$datos = isset($_REQUEST['Datos']) ? $_REQUEST['Datos'] : '';
$datos = (array) json_decode($datos, true);

if (!isset($datos['Id_Plan_Cuentas']) || !isset($datos['Id_Centro_Costo'])) {
    echo json_encode(['Codigo' => 1, 'Mensaje' => 'Datos incompletos']);
    exit;
}

$id = isset($datos['Id_Plan_Cuentas_Centro_Costos']) ? $datos['Id_Plan_Cuentas_Centro_Costos'] : '';
$estado = isset($datos['Estado']) && $datos['Estado'] != '' ? $datos['Estado'] : 'Activo';

if ($id != '') {
    $oItem = new complex('Plan_Cuentas_Centro_Costos', 'Id_Plan_Cuentas_Centro_Costos', $id);
} else {
    $oItem = new complex('Plan_Cuentas_Centro_Costos', 'Id_Plan_Cuentas_Centro_Costos');
}

$oItem->Id_Plan_Cuentas = $datos['Id_Plan_Cuentas'];
$oItem->Id_Centro_Costo = $datos['Id_Centro_Costo'];
$oItem->Estado = $estado;
$oItem->save();

echo json_encode(['Codigo' => 0, 'Mensaje' => 'Guardado correctamente']);
