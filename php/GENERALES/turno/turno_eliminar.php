<?php
// Inicio llamado funciones basicas sistema
require_once("../../config/start.inc.php");
include_once($MY_CLASS . "class.complex.php");
include_once($MY_CLASS . "class.imageresize.php");
include_once($MY_CLASS . "class.dao.php");
// Fin llamado funciones basicas sistema

// Inicio captura variables
$id  = (isset($_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
// Final captura variables

// funcion borrar
$oItem = new complex('Turno','Id_Turno',$id);
$oItem->delete();
unset($oItem);


$oLista= new lista('Hora_Turno');
$oLista->setRestric("Id_Turno","=",$id);
$horas=$oLista->getList();
unset($oLista);


foreach($horas as $hora){
	$oItem = new complex('Hora_Turno','Id_Hora_Turno',$hora["Id_Hora_Turno"]);
	$oItem->delete();
	unset($oItem);
}
echo "Turno Eliminado Correctamente"; 

?>