<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query2 = 'SELECT * FROM Zona  ';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query2);
$zona = $oCon->getData();
unset($oCon);


echo json_encode($zona);

?>
