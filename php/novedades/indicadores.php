<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = "SELECT D.Nombre AS Dependencia, COUNT(N.Id_Novedad) AS Cantidad, (SELECT COUNT(*) FROM Novedad) AS Total FROM Novedad N INNER JOIN Funcionario F ON N.Identificacion_Funcionario = F.Identificacion_Funcionario INNER JOIN Dependencia D ON F.Id_Dependencia = D.Id_Dependencia GROUP BY F.Id_Dependencia" ;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado['Grafica'] = $oCon->getData();
unset($oCon);

foreach ($resultado['Grafica'] as $i => $value) {
    $resultado['Grafica'][$i]['porcentaje'] = round(($resultado['Grafica'][$i]['Cantidad']/$resultado['Grafica'][$i]['Total'])*100,2);
}

$query = "SELECT (SELECT COUNT(*) FROM Novedad N INNER JOIN Tipo_Novedad TN ON N.Id_Tipo_Novedad = TN.Id_Tipo_Novedad WHERE TN.Tipo_Novedad = 'Vacaciones') AS Vacaciones, (SELECT COUNT(*) FROM Novedad N INNER JOIN Tipo_Novedad TN ON N.Id_Tipo_Novedad = TN.Id_Tipo_Novedad WHERE TN.Tipo_Novedad = 'Incapacidad') AS Incapacidad, (SELECT COUNT(*) FROM Novedad N INNER JOIN Tipo_Novedad TN ON N.Id_Tipo_Novedad = TN.Id_Tipo_Novedad WHERE TN.Tipo_Novedad = 'Permiso') AS Permiso, (SELECT COUNT(*) FROM Novedad N INNER JOIN Tipo_Novedad TN ON N.Id_Tipo_Novedad = TN.Id_Tipo_Novedad WHERE TN.Tipo_Novedad = 'Licencia') AS Licencia";

$oCon= new consulta();
$oCon->setQuery($query);
$resultado['Contadores'] = $oCon->getData();
unset($oCon);

echo json_encode($resultado);
?>