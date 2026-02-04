<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php'); 
include_once('../../class/class.complex.php'); 
 
$oItem = new complex('Categorias_Memorando','Id_categorias_memorando');
$oItem->Identificacion_Funcionario=$datos['Identificacion_Funcionario'];
$oItem->Tipo="Memorando";
$oItem->Detalles="Se le ha generado un memorando por el motivo ".$datos['Motivo'];
$oItem->Id=$id_memorando;
$oItem->save();
unset($oItem);


?>