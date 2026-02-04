<?php
//header('Content-Type: application/json');

require_once("/home/sigespro/public_html/config/start.inc_cron.php");
include_once("/home/sigespro/public_html/class/class.lista.php");
include_once("/home/sigespro/public_html/class/class.complex.php");
include_once("/home/sigespro/public_html/class/class.consulta.php");

$fecha_hoy = date('Y-m-d');
$mes_anterior = strtotime("-1 month", strtotime($fecha_hoy));
$ultimo_dia_mes_anterior = date("Y-m-t", $mes_anterior);

$query = "DELETE FROM Borrador_Contabilidad WHERE DATE(Created_At) <= '$ultimo_dia_mes_anterior'";

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->createData();
unset($oCon);

echo "\n\nBORRADORES DE CONTABILIDAD ELIMINADOS CORRECTAMENTE -- Fecha: " . date('d/m/Y H:i:s') . "\n\n";

?>