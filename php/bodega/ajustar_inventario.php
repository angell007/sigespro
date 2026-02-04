<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$modulo = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );
$productos = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );

$productos = (array) json_decode($productos , true);


foreach($productos as $producto ){

$oItem = new complex($modulo,"Id_".$modulo,$producto['Id_Inventario']);
$oItem->Cantidad=$producto['Cantidad'];
$oItem->save();
unset($oItem);

}

?>