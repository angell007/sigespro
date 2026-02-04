<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$fecha_inicial = isset($_REQUEST['fecha_inicio']) ? $_REQUEST['fecha_inicio'] : false;
$fecha_final = isset($_REQUEST['fecha_fin']) ? $_REQUEST['fecha_fin'] : false;

$condicion = '';

if ($fecha_inicial && $fecha_final) {
    $condicion .= "WHERE llt.Fecha BETWEEN '$fecha_inicial' AND '$fecha_final'";
} else {
    $condicion .= "WHERE DATE_FORMAT(llt.Fecha, '%Y-%m-%d')=CURDATE()";
}
    $condicion .= " AND DATE_FORMAT(llt.Fecha, '%Y-%m-%d')>= '2022-11-01'";

$query = "SELECT llt.Id_Dependencia, d.Nombre AS Dependencia, COUNT(llt.Id_Dependencia) AS Cantidad, (SELECT COUNT(*) FROM Llegada_Tarde llt $condicion) AS Total FROM Llegada_Tarde llt INNER JOIN Dependencia d ON llt.Id_Dependencia=d.Id_Dependencia $condicion GROUP BY llt.Id_Dependencia";

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado['Grafica'] = $oCon->getData();
unset($oCon);

foreach ($resultado['Grafica'] as $i => $value) {
    $resultado['Grafica'][$i]['porcentaje'] = round(($value['Cantidad']/$value['Total'])*100,2);
}

$query = "SELECT (SELECT COUNT(*) FROM Llegada_Tarde llt $condicion) AS Total_Llegadas, ( SELECT SEC_TO_TIME(SUM(llt.Tiempo)) FROM Llegada_Tarde llt $condicion) AS Tiempo_Acumulado, (SELECT COUNT(*) FROM Diario_Fijo llt $condicion  ) AS Total ";


$oCon= new consulta();
$oCon->setQuery($query);
$datos= $oCon->getData();
unset($oCon);

if($datos['Total']!=0){
    $datos['Porcentaje']=number_format(($datos['Total_Llegadas']*100)/$datos['Total'],2,".","");
}else{
    $datos['Porcentaje']=0;
}


$resultado['Contadores']=$datos;
echo json_encode($resultado);
?>