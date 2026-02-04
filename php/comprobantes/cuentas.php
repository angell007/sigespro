<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

/* $query = "SELECT Id_Plan_Cuentas AS value, CONCAT_WS(' ',Codigo,'-',Nombre) AS label FROM Plan_Cuentas WHERE (Nombre LIKE 'BANCO%' AND  Codigo LIKE '11%' AND CHAR_LENGTH(Codigo)>4) OR Nombre LIKE 'TARJETA%' OR Nombre LIKE 'CAJA%' ORDER BY Nombre"; */ // COMENTADO POR KENDRY EL DÍA 03/04/2019

$query = "SELECT Id_Plan_Cuentas AS value, CONCAT_WS(' ',Codigo,'-',Nombre) AS label FROM Plan_Cuentas WHERE Banco = 'S' AND Cod_Banco IS NOT NULL";

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultados = $oCon->getData();
unset($oCon);

echo json_encode($resultados);

?>