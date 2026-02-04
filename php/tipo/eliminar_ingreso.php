<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id_ingreso = ( isset( $_REQUEST['id_ingreso'] ) ? $_REQUEST['id_ingreso'] : '' );


$oItem = new complex("Tipo_Ingreso","Id_Tipo_Ingreso",(INT)$id_ingreso);
$oItem->delete();
unset($oItem);

$resultado["mensaje"]="Se ha eliminado el Tipo de Ingreso Correctamente!";
$resultado["tipo"]="success";
echo json_encode($resultado);

?>