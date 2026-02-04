<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );


$query = 'SELECT C.*
FROM Correspondencia C
WHERE C.Estado="Enviada" AND C.Id_Funcionario_Envia ='.$id;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$datos = $oCon->getData();
unset($oCon);

//var_dump($datos);
//$resultado = json_decode();
//$datos["Texto"]=stripslashes($datos["Texto"]);

echo json_encode($datos);


?>