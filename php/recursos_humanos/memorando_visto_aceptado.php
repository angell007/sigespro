<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
//header('Content-Type: application/json');
header("Content-type: application/pdf");
header("Content-Disposition:attachment;filename='downloaded.pdf'");

include_once('../../config/start.inc.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.mensajes.php');
include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.php_mailer.php');

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$datos = (array)json_decode($datos);

//Guarda la fecha en el que el memorando fue visto
$oItem = new complex("Memorando","Id_Memorando",$datos['Id']);
$oItem->Fecha                     =$datos['Fecha'];
$oItem->Fecha_Visto               =date('Y-m-d H:i:s');
$oItem->save();
unset($oItem);



$oItem = new complex("Alerta","Id_Alerta",$datos['Id_Alerta']);
$oItem->Respuesta = "Si"   ;
$oItem->save();
unset($oItem);


