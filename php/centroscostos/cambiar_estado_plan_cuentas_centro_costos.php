<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once '../../config/start.inc.php';
include_once '../../class/class.complex.php';

$id = isset($_REQUEST['Id_Plan_Cuentas_Centro_Costos']) ? $_REQUEST['Id_Plan_Cuentas_Centro_Costos'] : '';
$estado = isset($_REQUEST['Estado']) ? $_REQUEST['Estado'] : '';

if ($id == '' || $estado == '') {
    echo json_encode(['Codigo' => 1, 'Mensaje' => 'Parametros incompletos']);
    exit;
}

$oItem = new complex('Plan_Cuentas_Centro_Costos', 'Id_Plan_Cuentas_Centro_Costos', $id);
$oItem->Estado = $estado;
$oItem->save();

echo json_encode(['Codigo' => 0, 'Mensaje' => 'Estado actualizado']);
