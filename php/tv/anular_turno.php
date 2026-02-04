<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$turnero = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );


$oItem = new complex("Turnero","Id_Turnero",$turnero);
$oItem->Estado="Anulado";
$oItem->save();
unset($oItem);

$final["Estado"]='Anulado';

echo json_encode($final);
?>