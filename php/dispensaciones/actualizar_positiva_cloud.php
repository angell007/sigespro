<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$datos = json_decode($datos, true);

if ($datos["Id"] != '') {

    $oItem = new complex('Positiva_No_Autorizados_App',"Id",$datos['Id']);
    $oItem->Codigo=$datos["Codigo"];
    $oItem->Estado="Radicado";
    $oItem->save();
    unset($oItem);
}

$resultado['titulo']   = "Radicación";
$resultado['mensaje'] = "Se Radicó el Documentento exitosamente.";
$resultado['tipo']    = "success";


echo json_encode($resultado);

