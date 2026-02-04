<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.mensajes.php');


$direccion = isset($_REQUEST['direccion']) ? $_REQUEST['direccion'] : '';
$telefono = isset($_REQUEST['telefono']) ? $_REQUEST['telefono'] : '';
$fecha = isset($_REQUEST['fecha']) ? $_REQUEST['fecha'] : '';
$hora = isset($_REQUEST['hora']) ? $_REQUEST['hora'] : '';
$id_dispensacion = isset($_REQUEST['id_dispensacion']) ? $_REQUEST['id_dispensacion'] : '';
$id_dispensacion_domicilio = isset($_REQUEST['id_dispensacion_domicilio']) ? $_REQUEST['id_dispensacion_domicilio'] : '';

$sms_sender = new Mensaje();

$direccion = json_decode($direccion, true);
$tipoRaw = isset($direccion['Tipo_Direccion']) ? trim($direccion['Tipo_Direccion']) : '';
if ($tipoRaw === 'Punto Dispensacion' || $tipoRaw === 'Punto_Dispensacion') {
    $tipo = 'Punto_Dispensacion';
} elseif ($tipoRaw === 'Paciente') {
    $tipo = 'Paciente';
} elseif ($tipoRaw === 'Bodega' || $tipoRaw === 'Bodega_Nuevo') {
    $tipo = 'Bodega_Nuevo';
} else {
    $tipo = '';
}

try {

    $oItem = new complex('Dispensacion_Domicilio', 'Id_Dispensacion', $id_dispensacion);
    $oItem->Id_Dispensacion  = $id_dispensacion;
    $oItem->Estado = "Confirmado";
    $oItem->Id_Paciente_Telefono = $telefono;
    $oItem->Fecha_Entrega = $fecha;
    $oItem->Hora_Entrega = $hora;
    $oItem->Tipo_Direccion = $tipo;
    $oItem->Id_Direccion = isset($direccion['Id_Paciente_Direccion']) ? $direccion['Id_Paciente_Direccion'] : '';
    $oItem->save();


    $oItem = new complex('Paciente_Telefono', 'Id_Paciente_Telefono', $telefono);
    $cell = $oItem->getData();


    //enviar mensaje de texto
    $enviado = $sms_sender->Enviar($cell['Numero_Telefono'], 'Hemos actualizado la informaci¨®n de la entrega de tus Medicamentos, estamos coordinanado tu despacho - ProH ');



    echo json_encode([
        'message' => 'Guardado exitosamente',
        'type' => 'success',
        'title' => 'Direcci¨®n guardada exitosamente'
    ]);
} catch (Exception $err) {
    var_dump($error->getMessage());
}
