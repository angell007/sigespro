<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query2 = 'SELECT COUNT(*) as Enviadas,
(SELECT COUNT(*) as total FROM Cotizacion_Venta WHERE Estado_Cotizacion_Venta = "Pendiente") as Pendientes,
(SELECT COUNT(*) as total FROM Cotizacion_Venta WHERE Estado_Cotizacion_Venta = "Aprobada") as Aprobadas,
(SELECT COUNT(*) as total FROM Cotizacion_Venta WHERE Estado_Cotizacion_Venta = "Anulada") as Anuladas,
(SELECT COUNT(*) as total FROM Cotizacion_Venta WHERE Estado_Cotizacion_Venta = "No_Aprobadas") as No_Aprobadas
FROM Cotizacion_Venta';

$oCon= new consulta();
$oCon->setQuery($query2);
$indicadores = $oCon->getData();
unset($oCon);


$porcentaje = $indicadores["Aprobadas"]*100/$indicadores["Enviadas"];
$indicadores["Porcentaje"]=$porcentaje;

echo json_encode($indicadores);

?>