<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = "SELECT CONCAT(Id_Cliente,' - ',Nombre) AS Nombre, Id_Cliente as Id  FROM Cliente ";


$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado['Cliente'] = $oCon->getData();
unset($oCon);

$query = "SELECT CONCAT(Id_Proveedor,' - ',Nombre) AS Nombre, Id_Proveedor as Id FROM Proveedor ";


$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado['Proveedor'] = $oCon->getData();
unset($oCon);

echo json_encode($resultado);

