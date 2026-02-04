<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = "SELECT Id_Departamento AS value, Nombre AS label FROM Departamento";

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$departamentos = $oCon->getData();
unset($oCon);

echo json_encode($departamentos);
?>