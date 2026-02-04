<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = "SELECT T.Nombre AS Dependencia, COUNT(A.Id_Auditoria) as Cantidad, (SELECT COUNT(*) FROM Auditoria) as Total
FROM Auditoria A
INNER JOIN Tipo_Servicio T 
ON A.Id_Tipo_Servicio=T.Id_Tipo_Servicio
GROUP BY A.Id_Tipo_Servicio" ;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado['Grafica'] = $oCon->getData();
unset($oCon);

foreach ($resultado['Grafica'] as $i => $value) {
    $resultado['Grafica'][$i]['porcentaje'] = round(($resultado['Grafica'][$i]['Cantidad']/$resultado['Grafica'][$i]['Total'])*100,2);
}


echo json_encode($resultado);
?>