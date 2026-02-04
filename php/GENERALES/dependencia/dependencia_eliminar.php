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
$oItem = new complex('Dependencia','Id_Dependencia',$id);
$oItem->delete();
unset($oItem);

echo "Dependencia Eliminada Correctamente"; 

?>