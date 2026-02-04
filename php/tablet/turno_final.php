<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$persona = ( isset( $_REQUEST['Persona'] ) ? $_REQUEST['Persona'] : '' );
$tipo = ( isset( $_REQUEST['Tipo'] ) ? $_REQUEST['Tipo'] : '' );
$punto = ( isset( $_REQUEST['Punto'] ) ? $_REQUEST['Punto'] : '' );
$estado = ( isset( $_REQUEST['Estado'] ) ? $_REQUEST['Estado'] : '' );


$oItem = new complex("Reclamante","Id_Reclamante",$persona);
$per = $oItem->getData();
unset($oItem);

$oItem = new complex("Turnero","Id_Turnero");
$oItem->Identificacion_Persona=$persona;
$oItem->Persona = $per["Nombre"];  
$oItem->Id_Turneros = $punto;
$oItem->Fecha = date("Y-m-d");
$oItem->Hora_Turno = date("H:i:s");
if($tipo=="Capita"){
   $oItem->Estado = "Espera"; 
}elseif($tipo=="OtroServicio"){
  $oItem->Estado = "Auditoria";
}
$oItem->Tipo = $tipo;
$oItem->Tag = $estado;
$oItem->save();
unset($oItem);

$final["Error"]="No";
$final["Tipo"] = "success"; 
$final["Solicitar"] = "Si";
$final["Persona"]=$per["Nombre"];
$final["Cedula"] = $persona;
$final["Mensaje"]="Su turno se ha asignado Correctamente en la pantalla.";

echo json_encode($final);

?>