<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

date_default_timezone_set('America/Bogota');


require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once('../../class/class.configuracion.php');
include_once('../../class/class.consulta.php');

$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );
$modelo =  json_decode($modelo,true );

$oItem=new complex('Precio_Regulado',"Id_Precio_Regulado",$modelo['Id_Precio_Regulado']);
$oItem->delete();
unset($oItem);

$resultado['mensaje']="Se ha Eliminado Correctamente!";
$resultado['tipo']="success";

echo json_encode($resultado);

?>