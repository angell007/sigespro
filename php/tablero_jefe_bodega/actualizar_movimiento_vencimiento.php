<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once('../../class/class.configuracion.php');
include_once('../../class/class.consulta.php');
date_default_timezone_set('America/Bogota'); 

$id = ( isset( $_REQUEST['Id'] ) ? $_REQUEST['Id'] : '' );
$user = ( isset( $_REQUEST['user'] ) ? $_REQUEST['user'] : '' );
$observacion = ( isset( $_REQUEST['observacion'] ) ? $_REQUEST['observacion'] : '' );

$oItem = new complex('Movimiento_Vencimiento',"Id_Movimiento_Vencimiento",$id );
$oItem->Estado='Aprobada';
$oItem->Funcionario_Aprueba=$user;
$oItem->Fecha_Aprobacion= date('Y-m-d H:i:s');
$oItem->Observacion_Aprobacion= $observacion;
$oItem->save();
unset($oItem);

$resultado['mensaje'] = "Se guardado correctamente el  movimiento de los vencimientos";
$resultado['tipo'] = "success";


echo json_encode($resultado);

?>	