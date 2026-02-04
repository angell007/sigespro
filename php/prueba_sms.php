<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../class/class.mensajes.php');

$oCon= new Mensaje();
$resultado=$oCon->Enviar('3173824618','Freddy, PROH te informa que los sms desde SIGESPRO funcionan correctamente. Att. CorvusLab');
unset($oCon);

var_dump($resultado); 

?>