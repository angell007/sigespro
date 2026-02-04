<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

if ($id != '') {
	$oItem = new complex('No_Conforme', 'Id_No_Conforme', $id);
	$oItem->Estado = 'Cerrado';
	$oItem->save();
	unset($oItem);
}

$resultado['success'] = true;

echo json_encode($resultado);
?>