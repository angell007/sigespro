<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$punto = ( isset( $_REQUEST['Punto'] ) ? $_REQUEST['Punto'] : '' );

$datos = (array) json_decode($datos);

$oLista = new Lista("Reclamante");
$oLista->setRestrict("Id_Reclamante","=",$persona);
$reclamantes = $oLista->getList();
unset($oLista);


$oItem = new complex("Reclamante","Id_Reclamante");
foreach($datos as $index=>$value) {
    $oItem->$index=$value;
}
$oItem->save();
unset($oItem);
	
$oItem = new complex("Turnero","Id_Turnero");
$oItem->Identificacion_Persona=$datos["Id_Reclamante"];
$oItem->Persona = $datos["Nombre"];  
$oItem->Id_Turneros = $punto;
$oItem->Fecha = date("Y-m-d");
$oItem->Hora_Turno = date("H:i:s");
$oItem->Estado = "Espera";
$oItem->save();
unset($oItem);

$final["Error"]="No";
$final["Tipo"] = "success"; 
$final["Persona"]=$datos["Nombre"];
$final["Mensaje"]="Su turno se ha asignado correctamente en la pantalla, Tiempo de espera promedio: 8 Minutos";


echo json_encode($final);
?>