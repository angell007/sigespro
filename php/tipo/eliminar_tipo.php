<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );


$oItem = new complex("Tipo_Rechazo","Id_Tipo_Rechazo",(INT)$id);
$oItem->delete();
unset($oItem);

$resultado["mensaje"]="Se ha eliminado el Tipo de Rechazo Correctamente!";
$resultado["tipo"]="success";
echo json_encode($resultado);

?>