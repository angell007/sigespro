<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );

$datos = (array) json_decode($datos);

if (isset($datos['Id_Estado_Financiero']) && $datos['Id_Estado_Financiero'] != '') {
    $oItem = new complex('Estado_Financiero','Id_Estado_Financiero', $datos['Id_Estado_Financiero']);
    $id = $oItem->Id_Estado_Financiero;
} else {
    $oItem = new complex('Estado_Financiero','Id_Estado_Financiero');
}

foreach ($datos as $index => $value) {
    $oItem->$index = $value;
}
$oItem->save();
$id = $oItem->getId();
unset($oItem);

if ($id) {
    $resultado['mensaje']="Retencion creada Correctamente!";
    $resultado['tipo']="success";
    $resultado['title']="Exitoso!"; 
} else {
    $resultado['mensaje']="Lo sentimos, ha ocurrido inesperado en el proceso.";
    $resultado['tipo']="error";
    $resultado['title']="Error!";
}



echo json_encode($resultado);
?>