<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

date_default_timezone_set('America/Bogota');

require_once('../../config/start.inc.php');
include_once('../../class/class.querybasedatos.php');

$queryObj = new QueryBaseDatos();
$desde = date('Y-m-d H:i:s', strtotime('-7 days'));

// KPIs
$queryObj->SetQuery("SELECT COUNT(*) AS total FROM Solicitud_Sigespro WHERE Estado_Solicitud = 'Finalizada' AND Aprobacion_Solicitante >= '".$desde."'");
$finalizadas = $queryObj->ExecuteQuery('simple');

$queryObj->SetQuery("SELECT COUNT(DISTINCT Id_Solicitud_Sigespro) AS total FROM Actividad_Solicitud_Sigespro WHERE Fecha_Actividad >= '".$desde."'");
$actualizadas = $queryObj->ExecuteQuery('simple');

$queryObj->SetQuery("SELECT COUNT(*) AS total FROM Solicitud_Sigespro WHERE Fecha_Solicitud >= '".$desde."'");
$creadas = $queryObj->ExecuteQuery('simple');

// Estado actual (snapshot)
$queryObj->SetQuery("SELECT Estado_Solicitud AS Estado_Solicitud, COUNT(*) AS Cantidad FROM Solicitud_Sigespro GROUP BY Estado_Solicitud");
$estados = $queryObj->ExecuteQuery('multiple');

// Tipos de solicitud (ultimos 7 dias)
$queryObj->SetQuery("SELECT Tipo_Solicitud AS Tipo_Solicitud, COUNT(*) AS Cantidad FROM Solicitud_Sigespro WHERE Fecha_Solicitud >= '".$desde."' GROUP BY Tipo_Solicitud");
$tipos = $queryObj->ExecuteQuery('multiple');

// Areas reportadas (ultimos 7 dias)
$queryObj->SetQuery("SELECT Area_Sistema AS Area_Sistema, COUNT(*) AS Cantidad FROM Solicitud_Sigespro WHERE Fecha_Solicitud >= '".$desde."' GROUP BY Area_Sistema ORDER BY Cantidad DESC LIMIT 6");
$areas = $queryObj->ExecuteQuery('multiple');

// Modulos reportados (ultimos 7 dias)
$queryObj->SetQuery("SELECT Modulo_Sistema AS Modulo_Sistema, COUNT(*) AS Cantidad FROM Solicitud_Sigespro WHERE Fecha_Solicitud >= '".$desde."' GROUP BY Modulo_Sistema ORDER BY Cantidad DESC LIMIT 6");
$modulos = $queryObj->ExecuteQuery('multiple');

// Actividad reciente (ultimos 7 dias)
$primerDiaMes = date('Y-m-01');
$ultimoDiaMes  = date('Y-m-t');

$query = "
    SELECT 
        ASS.Id_Solicitud_Sigespro,
        ASS.Fecha_Actividad AS Fecha,
        ASS.Detalle,
        ASS.Tipo_Actividad,
        SS.Observacion,
        SS.Estado_Solicitud AS Estado,
        CONCAT_WS(' ', F.Nombres, F.Apellidos) AS Funcionario
    FROM Actividad_Solicitud_Sigespro ASS
    INNER JOIN Solicitud_Sigespro SS ON ASS.Id_Solicitud_Sigespro = SS.Id_Solicitud_Sigespro
    INNER JOIN Funcionario F ON ASS.Id_Funcionario = F.Identificacion_Funcionario
    WHERE ASS.Fecha_Actividad BETWEEN '".$primerDiaMes."' AND '".$ultimoDiaMes." 23:59:59'
    ORDER BY ASS.Fecha_Actividad DESC
";
$queryObj->SetQuery($query);
$actividad = $queryObj->ExecuteQuery('multiple');

echo json_encode([
    'kpis' => [
        'finalizadas' => intval($finalizadas['total']),
        'actualizadas' => intval($actualizadas['total']),
        'creadas' => intval($creadas['total'])
    ],
    'estados' => $estados ? $estados : [],
    'tipos' => $tipos ? $tipos : [],
    'areas' => $areas ? $areas : [],
    'modulos' => $modulos ? $modulos : [],
    'actividad' => $actividad ? $actividad : []
]);
?>
