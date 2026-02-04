<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$user = ( isset( $_REQUEST['user'] ) ? $_REQUEST['user'] : '' );
$clave = ( isset( $_REQUEST['clave'] ) ? $_REQUEST['clave'] : '' );
//echo $user;
//echo $clave;

$oItem = new complex("Funcionario","Identificacion_Funcionario",$user);
$oItem->Password=md5($clave);
$oItem->save();
unset($oItem);

$resultado["Mensaje"]="Clave cambiada exitosamente. /n Su nueva contraseña es: ".$clave;

echo json_encode($resultado);

?>