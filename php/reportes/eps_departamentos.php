<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$query = "SELECT E.Nombre,CT.Id_Cliente FROM Contrato CT  STRAIGHT_JOIN Eps E ON CT.Id_Cliente=E.Nit ORDER BY Nombre ASC ";

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado['Eps'] = $oCon->getData();
unset($oCon);
$query = "SELECT Id_Departamento,Nombre FROM Departamento ORDER BY Nombre ASC  ";

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado['Departamento'] = $oCon->getData();
unset($oCon);

echo json_encode($resultado);

?>