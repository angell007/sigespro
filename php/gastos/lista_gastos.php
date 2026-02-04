<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condicion = '';

if (isset($_REQUEST['Identificacion_Funcionario']) && $_REQUEST['Identificacion_Funcionario'] != '') {
    $condicion .= " WHERE GP.Identificacion_Funcionario = $_REQUEST[Identificacion_Funcionario]";
}

$query = "SELECT GP.Id_Gasto_Punto, GP.Fecha, F.Nombre_Funcionario, PD.Nombre AS Punto_Dispensacion, 
GP.Codigo, IFNULL(GP.Codigo_Legalizacion, 'Sin legalizar') AS Codigo_Legalizacion, 
GP.Fecha_Aprobacion, F2.Nombre_Funcionario AS Funcionario_Aprobacion,
 GP.Estado, (CASE GP.Estado WHEN 'Anulada' THEN 1 WHEN 'Pendiente' THEN 2 WHEN 'Aprobado' THEN 3 END) 
 AS Estado_Order FROM Gasto_Punto GP 
 INNER JOIN
  (SELECT Identificacion_Funcionario, CONCAT_WS(' ',Nombres,Apellidos) AS Nombre_Funcionario 
  FROM Funcionario) F ON (GP.Identificacion_Funcionario = F.Identificacion_Funcionario) 
  LEFT JOIN (SELECT Identificacion_Funcionario, CONCAT_WS(' ',Nombres,Apellidos) AS Nombre_Funcionario 
  FROM Funcionario) F2 ON (GP.Funcionario_Aprobacion = F2.Identificacion_Funcionario) 
  INNER JOIN Punto_Dispensacion PD ON GP.Id_Punto_Dispensacion = PD.Id_Punto_Dispensacion 
  $condicion ORDER BY GP.Fecha";

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$gastos = $oCon->getData();
unset($oCon);

$total = count($gastos);

echo json_encode($gastos);
?>