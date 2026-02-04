<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');



$query = 'SELECT R.*  FROM Tipo_Rechazo  R  ';


$oCon= new consulta();

$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$causal = $oCon->getData();
unset($oCon);



echo json_encode($causal);
?>