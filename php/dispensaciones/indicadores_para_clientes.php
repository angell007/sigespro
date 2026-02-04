<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.querybasedatos.php');

$queryObj = new QueryBaseDatos();

$condicion = getCondiciones();

$query = "SELECT
SUM(r.Total) AS Total,
MAX(CASE WHEN r.Id_Servicio = 1 THEN r.Total END) AS Total_Pos,
MAX(CASE WHEN r.Id_Servicio = 2 THEN r.Total END) AS Total_NoPos,
SUM(r.Pendientes) AS Pendientes
FROM
(
    SELECT 
    Id_Servicio, 
    COUNT(*) AS Total, 
    COUNT(CASE WHEN Pendientes > 0 THEN 1 ELSE NULL END) AS Pendientes 
    FROM Dispensacion D
    INNER JOIN Paciente PC ON PC.Id_Paciente = D.Numero_Documento
    WHERE Estado_Dispensacion != 'Anulada' AND (DATE(D.Fecha_Actual) BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND CURDATE()) $condicion
    GROUP BY Id_Servicio
) r";

$queryObj->SetQuery($query);
$indicadores['Numericos'] = $queryObj->ExecuteQuery('simple');

$query = "SELECT D.Id_Tipo_Servicio, CONCAT(S.Nombre,' - ',TS.Nombre) AS Nombre, COUNT(D.Id_Dispensacion) AS Total FROM Dispensacion D INNER JOIN Paciente PC ON PC.Id_Paciente = D.Numero_Documento INNER JOIN Tipo_Servicio TS ON TS.Id_Tipo_Servicio = D.Id_Tipo_Servicio INNER JOIN Servicio S ON S.Id_Servicio = TS.Id_Servicio WHERE Estado_Dispensacion != 'Anulada' AND (DATE(D.Fecha_Actual) BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND CURDATE()) $condicion GROUP BY D.Id_Tipo_Servicio";

$queryObj->SetQuery($query);
$resultado = $queryObj->ExecuteQuery('Multiple');

$ind['data'] = array_column($resultado, 'Total');
$ind['labels'] = array_column($resultado, 'Nombre');

foreach ($resultado as $i => $value) {
    $porcentaje = ($value['Total'] * 100) / $indicadores['Numericos']['Total'];
    $resultado[$i]['Porcentaje'] = number_format($porcentaje,2,".","");
}

$ind['tipos_servicios'] = $resultado;

$indicadores['Grafico'] = $ind;

echo json_encode($indicadores);

function getCondiciones() {
    $condicion = '';

    if (isset($_REQUEST['eps']) && $_REQUEST['eps'] != '') {
        $condicion .= " AND PC.Nit = $_REQUEST[eps]";
    }

    return $condicion;
}