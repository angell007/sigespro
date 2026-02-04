<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$turnero = ( isset( $_REQUEST['Id_Turnero'] ) ? $_REQUEST['Id_Turnero'] : '' );
$tipo = ( isset( $_REQUEST['Tipo'] ) ? $_REQUEST['Tipo'] : '' );


$oItem = new complex("Turnero","Id_Turnero",$turnero);
if($tipo=="Atender"){
    $oItem->Estado="Atendido";
    $oItem->Hora_Inicio_Atencion=date("H:i:s"); 
}elseif($tipo=="Anular"){
    $oItem->Estado="Anulado";
}
$oItem->save();
$aten = $oItem->getData();
unset($oItem);


$final["Turno"]=$aten;

echo json_encode($final);
?>