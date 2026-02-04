<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

// $query = "SELECT P.*, 
// CONCAT_WS(' ',F.Nombres,F.Apellidos) AS Nombre_Empleado,
// F.Imagen,
// (SELECT COUNT(Id_Prestamo_Cuota) FROM Prestamo_Cuota PC WHERE PC.Estado='Paga' AND PC.Id_Prestamo = P.Id_Prestamo) as Cuotas_Pagas
// FROM Prestamo P 
// INNER JOIN Funcionario F ON P.Identificacion_Funcionario = F.Identificacion_Funcionario";


$query = 'SELECT * FROM banco';

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$response = $oCon->getData();
unset($oCon);

echo json_encode($response);