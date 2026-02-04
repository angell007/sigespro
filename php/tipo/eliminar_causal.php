<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id_egreso = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );


$oItem = new complex("Causal_Anulacion","Id_Causal_Anulacion",(INT)$id_egreso);
$oItem->delete();
unset($oItem);

$resultado["mensaje"]="Se ha eliminado el causal de Anulacion Correctamente!";
$resultado["tipo"]="success";
echo json_encode($resultado);

?>