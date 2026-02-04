<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query1 = 'SELECT count(*) as conteoNoPagas, (SELECT count(*) FROM `Factura_Venta` WHERE Estado_Factura_Venta IS NOT NULL) as conteoPagas ,  (SELECT count(*) FROM `Factura_Venta`) as conteo FROM `Factura_Venta` WHERE Estado_Factura_Venta is NULL' ;

$oCon= new consulta();
$oCon->setQuery($query1);
$productos = $oCon->getData();
unset($oCon);

echo json_encode($lista);

?>