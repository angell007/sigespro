<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');



$query = 'SELECT PC.Id_Plan_Cuentas, PC.Id_Plan_Cuentas AS Id, PC.Codigo, PC.Codigo AS Codigo_Cuenta, CONCAT(PC.Nombre," - ",PC.Codigo) as Codigo, CONCAT(PC.Codigo," - ",PC.Nombre) as Nombre, PC.Centro_Costo
FROM Plan_Cuentas PC WHERE CHAR_LENGTH(PC.Codigo)>5 AND PC.Movimiento = "S"';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado['Activo'] = $oCon->getData();
unset($oCon);

$query = 'SELECT PC.Id_Plan_Cuentas, PC.Codigo AS Codigo_Cuenta, CONCAT(PC.Nombre," - ",PC.Codigo) as Codigo
FROM Plan_Cuentas PC WHERE CHAR_LENGTH(PC.Codigo)>5 AND PC.Movimiento = "S"';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado['Pasivo'] = $oCon->getData();
unset($oCon);

echo json_encode($resultado);
?>