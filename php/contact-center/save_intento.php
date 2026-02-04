<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once __DIR__ . '/../../config/start.inc.php';

$response = array();

if (isset($_POST['id_dispensacion']) && isset($_POST['observacion'])) {

    $id_dispensacion = $_POST['id_dispensacion'];
    $observacion = $_POST['observacion'];
    $id_funcionario = isset($_POST['id_funcionario']) ? $_POST['id_funcionario'] : (isset($_SESSION['User']['Identificacion_Funcionario']) ? $_SESSION['User']['Identificacion_Funcionario'] : 0);

    $oItem = new complex('Intentos_Call_Center', 'Id_Intento_Call_Center');
    $oItem->Id_Dispensacion = $id_dispensacion;
    $oItem->observacion = $observacion;
    $oItem->Fecha = date("Y-m-d H:i:s");
    $oItem->save();
    unset($oItem);

    // Al agregar un intento el proceso no se completó: la pre-dispensación vuelve a Pendiente
    // para que siga apareciendo en la lista de pendientes hasta que se cierre el contacto.
    $oEstado = new complex('Dispensacion_Mipres', 'Id_Dispensacion_Mipres', $id_dispensacion);
    $oEstado->Estado_Callcenter = 'Pendiente';
    $oEstado->save();
    unset($oEstado);

    $response['success'] = true;
    $response['message'] = 'Intento registrado correctamente';
} else {
    $response['success'] = false;
    $response['message'] = 'Faltan datos';
}

echo json_encode($response);
