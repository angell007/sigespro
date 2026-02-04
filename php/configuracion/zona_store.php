<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );

$datos = (array) json_decode($datos);

if (isset($datos['Id_Zona']) && $datos['Id_Zona'] != '') {
    $oItem = new complex('Zona',"Id_Zona", $datos['Id_Zona']);
} else {
    $oItem = new complex('Zona',"Id_Zona");
}

$oItem->Nombre=strtoupper($datos["Nombre"]);
$oItem->save();

$resultado['tipo'] = "success";
$resultado['mensaje'] = "Se ha registrado correctamente la Zona";
$resultado['titulo'] = "Operación Exitosa!";

echo json_encode($resultado);

?>