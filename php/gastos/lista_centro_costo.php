<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = "SELECT Id_Centro_Costo AS value, CONCAT(Codigo,' - ',Nombre) AS label FROM Centro_Costo WHERE Movimiento = 'Si' AND Estado = 'Activo' ORDER BY Codigo";

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$centros = $oCon->getData();
unset($oCon);

echo json_encode($centros);
?>