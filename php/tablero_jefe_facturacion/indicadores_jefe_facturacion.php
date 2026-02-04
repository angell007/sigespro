<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$query = 'SELECT (SELECT COUNT(*) FROM Factura_Capita) AS Total_Capitas, (SELECT COUNT(*) FROM Factura F INNER JOIN Dispensacion D ON F.Id_Factura = D.Id_Factura WHERE D.Tipo = "Evento") AS Total_Evento, (SELECT COUNT(*) FROM Factura F INNER JOIN Dispensacion D ON F.Id_Factura = D.Id_Factura WHERE D.Tipo = "NoPos") AS Total_NoPos, (SELECT COUNT(*) FROM Factura WHERE Tipo = "Homologo") AS Total_Homologo';

$oCon= new consulta();

$oCon->setQuery($query);
$indicadores = $oCon->getData();
unset($oCon);

echo json_encode($indicadores);
?>