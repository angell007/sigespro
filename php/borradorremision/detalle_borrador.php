<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = 'SELECT B.*
FROM Borrador B
WHERE B.Id_Borrador='.$id ;

$oCon= new consulta();
$oCon->setQuery($query);
$datos = $oCon->getData();
unset($oCon);

//var_dump($datos);
//$resultado = json_decode();
//$datos["Texto"]=stripslashes($datos["Texto"]);

echo json_encode($datos);


?>
