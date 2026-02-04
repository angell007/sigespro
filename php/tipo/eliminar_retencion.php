<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id_rentencion = ( isset( $_REQUEST['id_retencion'] ) ? $_REQUEST['id_retencion'] : '' );


$oItem = new complex("Retencion","Id_Retencion",(INT)$id_rentencion);
$oItem->delete();
unset($oItem);

$resultado["mensaje"]="Se ha eliminado la Retencion Correctamente";
$resultado["tipo"]="success";
echo json_encode($resultado);

?>