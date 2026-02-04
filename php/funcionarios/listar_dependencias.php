<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = 'SELECT * FROM Dependencia';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$dependencia["dependencia"] = $oCon->getData();
unset($oCon);


echo json_encode($dependencia);

?>
