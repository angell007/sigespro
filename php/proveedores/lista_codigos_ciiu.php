<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = "SELECT Id_Codigo_Ciiu AS value, CONCAT(Codigo, ' - ', Descripcion) AS label FROM Codigo_Ciiu ORDER BY Codigo";

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$codigos = $oCon->getData();
unset($oCon);

echo json_encode($codigos);
?>