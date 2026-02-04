<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

date_default_timezone_set('America/Bogota');

$id  = (isset($_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$func = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );

$oItem = new complex('Remision','Id_Remision',$id);
$oItem->Estado = "Anulada";
$oItem->Funcionario_Anula=$func;
$oItem->Fecha_Anulacion= date("Y-m-d H:i:s");
$oItem->save();
unset($oItem);

$oItem = new complex('Actividad_Remision',"Id_Actividad_Remision");
$oItem->Id_Remision=$id;
$oItem->Identificacion_Funcionario=$func;
$oItem->Detalles="Se ha anulado esta remision";
$oItem->Fecha=date("Y-m-d H:i:s");
$oItem->save();
unset($oItem);


echo json_encode($resultado);
?>