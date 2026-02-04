<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = 'SELECT count(*) as total , (SELECT count(*) FROM `Nota_Credito` where MONTH(Fecha) = MONTH(NOW())) as mensual, sum(PNC.Subtotal) as Subtotal FROM Nota_Credito NC 
INNER JOIN Producto_Nota_Credito PNC 
ON NC.Id_Nota_Credito=PNC.Id_Nota_Credito' ;

$oCon= new consulta();
$oCon->setQuery($query);
$lista = $oCon->getData();
unset($oCon);

echo json_encode($lista);