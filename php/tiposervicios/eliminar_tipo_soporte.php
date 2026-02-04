<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id_tipo_soporte = ( isset( $_REQUEST['id_tipo_soporte'] ) ? $_REQUEST['id_tipo_soporte'] : '' );


$oItem = new complex("Tipo_Soporte","Id_Tipo_Soporte",(INT)$id_tipo_soporte);
$oItem->delete();
unset($oItem);


$resultado["mensaje"]="Se ha eliminado el Tipo de Soporte Correctamente";
$resultado["tipo"]="success";
echo json_encode($resultado);

?>