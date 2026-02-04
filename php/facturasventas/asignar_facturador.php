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

if ($datos) {
    $datos = (array) json_decode($datos, true);

    $Con = new complex('Dispensacion','Id_Dispensacion',$datos['id']);
    if ($datos['Facturador_Asignado'] == null || $datos['Facturador_Asignado'] == '') {
        $Con->Facturador_Asignado = '0';
    } else {
        $Con->Facturador_Asignado = $datos['Facturador_Asignado'];
        $Con->Fecha_Asignado_Facturador  = date('Y-m-d H:i:s');
    }

    $Con->save();
    unset($Con);

    $resultado['title'] = "Exito!";
    $resultado['mensaje'] = "Facturador asignado correctamente.";
    $resultado['tipo'] = "success";
} else {
    $resultado['title'] = "Error";
    $resultado['mensaje'] = "Ha ocurrido un error durante el proceso, si el error persiste comunicate con soporte tecnico.";
    $resultado['tipo'] = "error";
}

echo json_encode($resultado);

?>		