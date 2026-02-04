<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once('../../class/class.configuracion.php');

$configuracion = new Configuracion();

$cod = $configuracion->Consecutivo("Vacante");


$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );
$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );

$datos = (array) json_decode($datos, true);

$datos["Codigo"] = $cod;
$datos["Fecha"] = date("Y-m-d");
$oItem = new complex($mod,"Id_Vacante");
foreach($datos as $index=>$value) {
    if (strpos($value, 'a.m') !== false || strpos($value, 'p.m') !== false) {
        $value = date('H:i:s', strtotime($value));
    }
    $oItem->$index=$value;
}

$oItem->save();
$id_vacante = $oItem->getId();
unset($oItem);

if($id_vacante != ""){
    $resultado['mensaje'] = "Se ha guardado correctamente la Vacante";
    $resultado['tipo'] = "success";
}else{
    $resultado['mensaje'] = "ha ocurrido un error guardando la informacion, por favor verifique";
    $resultado['tipo'] = "error";
}

echo json_encode($resultado);



?>