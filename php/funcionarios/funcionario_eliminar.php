<?php
// Inicio llamado funciones basicas sistema
require_once("../../config/start.inc.php");
include_once($MY_CLASS . "class.complex.php");
include_once($MY_CLASS . "class.imageresize.php");
include_once($MY_CLASS . "class.dao.php");
require_once 'HTTP/Request2.php';
// Fin llamado funciones basicas sistema

function fecha($str)
{
    $date = explode("/",$str);
    return $date[2] ."-".  $date[1] ."-". $date[0];
}


// Inicio captura variables
$identificacion_funcionario  = (isset($_REQUEST['Identificacion_Funcionario'] ) ? $_REQUEST['Identificacion_Funcionario'] : '' );
$date  = (isset($_REQUEST['date'] ) ? $_REQUEST['date'] : '' );
// Final captura variables
 
// funcion borrar
$oItem = new complex('Funcionario','Identificacion_Funcionario',$identificacion_funcionario);
$oItem->Fecha_Retiro=fecha($date);
$oItem->save();



?>