<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

date_default_timezone_set('America/Bogota');

require_once '../../config/start.inc.php';
require_once '../../class/html2pdf.class.php';
include_once '../../class/class.querybasedatos.php';
include_once '../../class/class.utility.php';
include_once '../../class/class.http_response.php';
include_once __DIR__ . '/permisos_sigespro.php';

$util = new Utility();
$http_response = new HttpResponse();
$queryObj = new QueryBaseDatos();

$mes = (isset($_REQUEST['mes']) && $_REQUEST['mes'] != '') ? $_REQUEST['mes'] : '';
$fechas = (isset($_REQUEST['fechas']) && $_REQUEST['fechas'] != '') ? $_REQUEST['fechas'] : '';
$funcionario_consulta = (isset($_REQUEST['funcionario_consulta']) && $_REQUEST['funcionario_consulta'] != '') ? $_REQUEST['funcionario_consulta'] : '';

$area = (isset($_REQUEST['area']) && $_REQUEST['area'] != '') ? $_REQUEST['area'] : '';
$modulo = (isset($_REQUEST['modulo']) && $_REQUEST['modulo'] != '') ? $_REQUEST['modulo'] : '';
$tipo_solicitud = (isset($_REQUEST['tipo_solicitud']) && $_REQUEST['tipo_solicitud'] != '') ? $_REQUEST['tipo_solicitud'] : '';
$estado = (isset($_REQUEST['estado']) && $_REQUEST['estado'] != '') ? $_REQUEST['estado'] : '';

$output = (isset($_REQUEST['output']) && $_REQUEST['output'] != '') ? $_REQUEST['output'] : 'D';
$ruta = (isset($_REQUEST['ruta']) && $_REQUEST['ruta'] != '') ? $_REQUEST['ruta'] : '';

if ($funcionario_consulta != '') {
    $permiso_gerencia = ObtenerPermisoModulo($funcionario_consulta, 'Solicitudes Sigespro - Gerencia');
    if (!ValidarPermiso($permiso_gerencia, 'Ver')) {
        echo json_encode(RespuestaPermisoDenegado());
        exit;
    }
}

list($fecha_ini, $fecha_fin, $periodo_label) = ObtenerRangoFechas($mes, $fechas, $util);

$filtros = ArmarFiltros($area, $modulo, $tipo_solicitud, $estado);
$filtros_sin_estado = ArmarFiltros($area, $modulo, $tipo_solicitud, '');

$condicion_solicitud = " WHERE SS.Fecha_Solicitud BETWEEN '$fecha_ini' AND '$fecha_fin' $filtros ";
$condicion_actividad = " WHERE ASS.Fecha_Actividad BETWEEN '$fecha_ini' AND '$fecha_fin' $filtros ";

$resumen = ObtenerResumen($queryObj, $condicion_solicitud);
$por_area = ObtenerTotalesPorCampo($queryObj, $condicion_solicitud, 'SS.Area_Sistema', 'Area_Sistema');
$por_modulo = ObtenerTotalesPorCampo($queryObj, $condicion_solicitud, 'SS.Modulo_Sistema', 'Modulo_Sistema');
$por_tipo = ObtenerTotalesPorCampo($queryObj, $condicion_solicitud, 'SS.Tipo_Solicitud', 'Tipo_Solicitud');
$por_desarrollador = ObtenerTotalesPorDesarrollador($queryObj, $condicion_solicitud);
$por_actividad = ObtenerTotalesActividad($queryObj, $condicion_actividad);
$tiempos = ObtenerTiemposPromedio($queryObj, $condicion_solicitud);
$medianas_solicitud = ObtenerMedianasSolicitud($queryObj, $condicion_solicitud);
$tiempos_dev_act = ObtenerTiemposDesarrolloActividad($queryObj, $fecha_ini, $fecha_fin);
$detalle = ObtenerDetalleSolicitudes($queryObj, $condicion_solicitud);


$oItem = new complex('Configuracion', 'Id_Configuracion', 1);
$config = $oItem->getData();
unset($oItem);

$content = ConstruirPdf(
    $config,
    $periodo_label,
    $resumen,
    $por_area,
    $por_modulo,
    $por_tipo,
    $por_desarrollador,
    $por_actividad,
    $tiempos,
    $medianas_solicitud,
    $tiempos_dev_act,
    $detalle,
    $area,
    $modulo,
    $tipo_solicitud,
    $estado
);

try {
    $html2pdf = new HTML2PDF('P', 'LETTER', 'Es', true, 'UTF-8', array(5, 5, 5, 5));
    $html2pdf->writeHTML($content);
    $nombre = 'Informe_Solicitudes_Gerencia_' . date('Ymd_His') . '.pdf';

    if ($output == 'F' && $ruta != '') {
        $html2pdf->Output($_SERVER['DOCUMENT_ROOT'] . $ruta, 'F');
    } else {
        $html2pdf->Output($nombre, 'D');
    }
} catch (HTML2PDF_exception $e) {
    echo $e;
    exit;
}

function ObtenerRangoFechas($mes, $fechas, $util) {
    if ($fechas != '') {
        $rango = $util->SepararFechas($fechas);
        $ini = $rango[0] . ' 00:00:00';
        $fin = $rango[1] . ' 23:59:59';
        $label = $rango[0] . ' a ' . $rango[1];
        return array($ini, $fin, $label);
    }

    if ($mes == '') {
        $mes = date('Y-m');
    }

    $ini = $mes . '-01 00:00:00';
    $fin = date('Y-m-t 23:59:59', strtotime($mes . '-01'));
    $label = $mes;
    return array($ini, $fin, $label);
}

function ArmarFiltros($area, $modulo, $tipo_solicitud, $estado) {
    $condicion = '';

    if ($area != '') {
        $condicion .= " AND SS.Area_Sistema = '$area' ";
    }
    if ($modulo != '') {
        $condicion .= " AND SS.Modulo_Sistema = '$modulo' ";
    }
    if ($tipo_solicitud != '') {
        $condicion .= " AND SS.Tipo_Solicitud = '$tipo_solicitud' ";
    }
    if ($estado != '') {
        $condicion .= " AND SS.Estado_Solicitud = '$estado' ";
    }

    return $condicion;
}

function ObtenerResumen($queryObj, $condicion_solicitud) {
    $query = "
        SELECT
            COUNT(*) AS Total,
            SUM(CASE WHEN SS.Estado_Solicitud = 'Recibida' THEN 1 ELSE 0 END) AS Recibidas,
            SUM(CASE WHEN SS.Estado_Solicitud = 'Aprobada' THEN 1 ELSE 0 END) AS Aprobadas,
            SUM(CASE WHEN SS.Estado_Solicitud = 'En Desarrollo' THEN 1 ELSE 0 END) AS En_Desarrollo,
            SUM(CASE WHEN SS.Estado_Solicitud = 'Realizada' THEN 1 ELSE 0 END) AS Realizadas,
            SUM(CASE WHEN SS.Estado_Solicitud = 'Finalizada' THEN 1 ELSE 0 END) AS Finalizadas,
            SUM(CASE WHEN SS.Estado_Solicitud = 'Rechazada' THEN 1 ELSE 0 END) AS Rechazadas,
            SUM(CASE WHEN SS.Estado_Solicitud = 'Devuelto' THEN 1 ELSE 0 END) AS Devueltas,
            SUM(CASE WHEN SS.Estado_Solicitud IN ('Aprobada','En Desarrollo') THEN 1 ELSE 0 END) AS WIP
        FROM Solicitud_Sigespro SS
        $condicion_solicitud
    ";
    $queryObj->SetQuery($query);
    return $queryObj->ExecuteQuery('simple');
}

function ObtenerTotalesPorCampo($queryObj, $condicion_solicitud, $campo, $alias) {
    $query = "
        SELECT
            $campo AS $alias,
            COUNT(*) AS Total
        FROM Solicitud_Sigespro SS
        $condicion_solicitud
        GROUP BY $campo
        ORDER BY Total DESC
    ";
    $queryObj->SetQuery($query);
    return $queryObj->ExecuteQuery('multiple');
}

function ObtenerTotalesPorDesarrollador($queryObj, $condicion_solicitud) {
    $query = "
        SELECT
            CASE
                WHEN SS.Desarrollador_Asignado IS NULL OR TRIM(SS.Desarrollador_Asignado) = '' THEN 'No Asignado'
                WHEN SS.Desarrollador_Asignado REGEXP '^[0-9]+$' AND FD.Identificacion_Funcionario IS NOT NULL THEN CONCAT_WS(' ', FD.Nombres, FD.Apellidos)
                ELSE TRIM(SS.Desarrollador_Asignado)
            END AS Desarrollador,
            COUNT(*) AS Total
        FROM Solicitud_Sigespro SS
        LEFT JOIN Funcionario FD ON FD.Identificacion_Funcionario = SS.Desarrollador_Asignado
        $condicion_solicitud
        GROUP BY Desarrollador
        ORDER BY Total DESC
    ";
    $queryObj->SetQuery($query);
    return $queryObj->ExecuteQuery('multiple');
}

function ObtenerTotalesActividad($queryObj, $condicion_actividad) {
    $query = "
        SELECT
            ASS.Tipo_Actividad AS Tipo_Actividad,
            COUNT(*) AS Total
        FROM Actividad_Solicitud_Sigespro ASS
        INNER JOIN Solicitud_Sigespro SS ON SS.Id_Solicitud_Sigespro = ASS.Id_Solicitud_Sigespro
        $condicion_actividad
        GROUP BY ASS.Tipo_Actividad
        ORDER BY Total DESC
    ";
    $queryObj->SetQuery($query);
    return $queryObj->ExecuteQuery('multiple');
}

function ObtenerTiemposPromedio($queryObj, $condicion_solicitud) {
    $query = "
        SELECT
            AVG(CASE
                WHEN SS.Fecha_Inicio_Labor IS NOT NULL
                AND SS.Fecha_Inicio_Labor != '0000-00-00 00:00:00'
                AND SS.Fecha_Fin_Labor IS NOT NULL
                AND SS.Fecha_Fin_Labor != '0000-00-00 00:00:00'
                THEN TIMESTAMPDIFF(HOUR, SS.Fecha_Inicio_Labor, SS.Fecha_Fin_Labor)
                ELSE NULL
            END) AS Promedio_Horas_Desarrollo,
            AVG(CASE
                WHEN SS.Aprobacion_Solicitante IS NOT NULL
                AND SS.Aprobacion_Solicitante != '0000-00-00 00:00:00'
                THEN TIMESTAMPDIFF(HOUR, SS.Fecha_Solicitud, SS.Aprobacion_Solicitante)
                ELSE NULL
            END) AS Promedio_Horas_Total,
            AVG(CASE
                WHEN SS.Aprobacion_Proh IS NOT NULL
                AND SS.Aprobacion_Proh != '0000-00-00 00:00:00'
                THEN TIMESTAMPDIFF(HOUR, SS.Fecha_Solicitud, SS.Aprobacion_Proh)
                ELSE NULL
            END) AS Promedio_Horas_Aprobacion
        FROM Solicitud_Sigespro SS
        $condicion_solicitud
    ";
    $queryObj->SetQuery($query);
    return $queryObj->ExecuteQuery('simple');
}

function ObtenerMedianasSolicitud($queryObj, $condicion_solicitud) {
    $query = "
        SELECT
            SS.Fecha_Solicitud,
            SS.Aprobacion_Solicitante,
            SS.Aprobacion_Proh
        FROM Solicitud_Sigespro SS
        $condicion_solicitud
    ";
    $queryObj->SetQuery($query);
    $rows = $queryObj->ExecuteQuery('multiple');

    $entrega = array();
    $aprobacion = array();

    foreach ($rows as $row) {
        if (!empty($row['Aprobacion_Solicitante']) && $row['Aprobacion_Solicitante'] != '0000-00-00 00:00:00') {
            $h = (strtotime($row['Aprobacion_Solicitante']) - strtotime($row['Fecha_Solicitud'])) / 3600;
            if ($h >= 0) { $entrega[] = $h; }
        }
        if (!empty($row['Aprobacion_Proh']) && $row['Aprobacion_Proh'] != '0000-00-00 00:00:00') {
            $h = (strtotime($row['Aprobacion_Proh']) - strtotime($row['Fecha_Solicitud'])) / 3600;
            if ($h >= 0) { $aprobacion[] = $h; }
        }
    }

    return array(
        'entrega_p50' => CalcularPercentil($entrega, 0.50),
        'aprobacion_p50' => CalcularPercentil($aprobacion, 0.50)
    );
}

function CalcularPercentil($values, $p) {
    if (count($values) == 0) { return 0; }
    sort($values);
    $idx = (int) ceil($p * count($values)) - 1;
    if ($idx < 0) { $idx = 0; }
    if ($idx >= count($values)) { $idx = count($values) - 1; }
    return $values[$idx];
}

function ObtenerTiemposDesarrolloActividad($queryObj, $fecha_ini, $fecha_fin) {
    $query = "
        SELECT
            Id_Solicitud_Sigespro,
            MIN(CASE WHEN Tipo_Actividad = 'Inicio Desarrollo' THEN Fecha_Actividad END) AS Inicio,
            MAX(CASE WHEN Tipo_Actividad = 'Fin Desarrollo' THEN Fecha_Actividad END) AS Fin
        FROM Actividad_Solicitud_Sigespro
        WHERE Fecha_Actividad BETWEEN '$fecha_ini' AND '$fecha_fin'
        GROUP BY Id_Solicitud_Sigespro
    ";
    $queryObj->SetQuery($query);
    $rows = $queryObj->ExecuteQuery('multiple');

    $duraciones = array();
    foreach ($rows as $row) {
        if (!$row['Inicio'] || !$row['Fin']) {
            continue;
        }
        $inicio = new DateTime($row['Inicio']);
        $fin = new DateTime($row['Fin']);
        if ($fin < $inicio) {
            continue;
        }
        $diff = $fin->getTimestamp() - $inicio->getTimestamp();
        $horas = $diff / 3600;
        $duraciones[] = $horas;
    }

    if (count($duraciones) == 0) {
        return array('count' => 0, 'avg' => 0, 'p50' => 0, 'p85' => 0);
    }

    sort($duraciones);
    $count = count($duraciones);
    $avg = array_sum($duraciones) / $count;
    $p50 = $duraciones[(int) ceil(0.50 * $count) - 1];
    $p85 = $duraciones[(int) ceil(0.85 * $count) - 1];

    return array('count' => $count, 'avg' => $avg, 'p50' => $p50, 'p85' => $p85);
}

function ObtenerDetalleSolicitudes($queryObj, $condicion_solicitud) {
    $query = "
        SELECT
            SS.Id_Solicitud_Sigespro,
            SS.Fecha_Solicitud,
            SS.Area_Sistema,
            SS.Modulo_Sistema,
            SS.Tipo_Solicitud,
            SS.Estado_Solicitud,
            SS.Observacion,
            SS.Fecha_Inicio_Labor,
            SS.Fecha_Fin_Labor,
            SS.Aprobacion_Solicitante,
            CONCAT_WS(' ', FU.Nombres, FU.Apellidos) AS Funcionario_Solicita,
            CONCAT_WS(' ', FUN.Nombres, FUN.Apellidos) AS Funcionario_Crea,
            CASE
                WHEN SS.Desarrollador_Asignado IS NULL OR TRIM(SS.Desarrollador_Asignado) = '' THEN 'No Asignado'
                WHEN SS.Desarrollador_Asignado REGEXP '^[0-9]+$' AND FD.Identificacion_Funcionario IS NOT NULL THEN CONCAT_WS(' ', FD.Nombres, FD.Apellidos)
                ELSE TRIM(SS.Desarrollador_Asignado)
            END AS Desarrollador
        FROM Solicitud_Sigespro SS
        INNER JOIN Funcionario FU ON SS.Identificacion_Funcionario_Solicita = FU.Identificacion_Funcionario
        INNER JOIN Funcionario FUN ON SS.Identificacion_Funcionario_Crea = FUN.Identificacion_Funcionario
        LEFT JOIN Funcionario FD ON FD.Identificacion_Funcionario = SS.Desarrollador_Asignado
        $condicion_solicitud
        ORDER BY SS.Fecha_Solicitud ASC
    ";
    $queryObj->SetQuery($query);
    return $queryObj->ExecuteQuery('multiple');
}

function ConstruirPdf(
    $config,
    $periodo_label,
    $resumen,
    $por_area,
    $por_modulo,
    $por_tipo,
    $por_desarrollador,
    $por_actividad,
    $tiempos,
    $medianas_solicitud,
    $tiempos_dev_act,
    $detalle,
    $area,
    $modulo,
    $tipo_solicitud,
    $estado
) {
    $style = "<style>
        .page-content{width:750px;}
        .section-title{font-size:12px;font-weight:bold;margin:14px 0 6px 0;color:#1a1a1a;border-bottom:1px solid #e5e7eb;padding-bottom:2px;}
        .small{font-size:8px;color:#444;}
        .kpi{border:1px solid #e0e0e0;border-radius:6px;padding:8px;background:#f8f8f8;}
        .kpi-title{font-size:8px;color:#666;}
        .kpi-value{font-size:18px;font-weight:bold;color:#111;}
        .chip{display:inline-block;padding:3px 6px;border-radius:10px;font-size:8px;background:#e9eefb;margin-right:4px;}
        .legend{font-size:8px;color:#333;}
        .bar-label{font-size:8px;color:#222;}
        .table-header{font-size:9px;background:#c6c6c6;text-align:center;padding:6px;}
        .table-cell{font-size:8px;padding:4px;border:1px solid #c6c6c6;}
        .table-cell-center{text-align:center;}
    </style>";

    $logo = $_SERVER['DOCUMENT_ROOT'] . 'assets/images/LogoProh.jpg';
    $empresa = isset($config['Nombre_Empresa']) ? $config['Nombre_Empresa'] : '';
    $nit = isset($config['NIT']) ? $config['NIT'] : '';
    $direccion = isset($config['Direccion']) ? $config['Direccion'] : '';
    $telefono = isset($config['Telefono']) ? $config['Telefono'] : '';

    $filtros = '';
    if ($area != '') { $filtros .= ' Área: ' . $area . ' |'; }
    if ($modulo != '') { $filtros .= ' Módulo: ' . $modulo . ' |'; }
    if ($tipo_solicitud != '') { $filtros .= ' Tipo: ' . $tipo_solicitud . ' |'; }
    if ($estado != '') { $filtros .= ' Estado: ' . $estado . ' |'; }
    if ($filtros != '') { $filtros = rtrim($filtros, ' |'); }

    $cabecera =
        '<table style="width:100%">' .
            '<tr>' .
                '<td style="width:70px;"><img src="' . $logo . '" style="width:60px;" alt="Pro-H" /></td>' .
                '<td style="width:460px;font-size:11px;line-height:16px;">' .
                    '<strong>' . $empresa . '</strong><br>' .
                    'N.I.T.: ' . $nit . '<br>' .
                    $direccion . '<br>' .
                    'Bucaramanga, Santander<br>' .
                    'TEL: ' . $telefono .
                '</td>' .
                '<td style="width:220px;text-align:right;">' .
                    '<div style="font-size:14px;font-weight:bold;">Informe Gerencia</div>' .
                    '<div style="font-size:10px;">Periodo: ' . $periodo_label . '</div>' .
                    '<div style="font-size:9px;">Fecha: ' . date('Y-m-d') . '</div>' .
                '</td>' .
            '</tr>' .
        '</table>';

    $resumen_html = ConstruirResumenTabla($resumen);
    $pie_html = ConstruirBarraEstados($resumen);
    $tiempos_promedio_html = ConstruirTiemposPromedio($medianas_solicitud, $tiempos_dev_act);

    $resumen_ejecutivo = ConstruirResumenEjecutivo($resumen);

    $por_area_html = ConstruirBarrasTop($por_area, 'Area_Sistema', 3);
    $por_modulo_html = ConstruirBarrasTop($por_modulo, 'Modulo_Sistema', 3);
    $por_tipo_html = ConstruirBarrasTop($por_tipo, 'Tipo_Solicitud', 3);
    $por_desarrollador_html = ConstruirBarrasTop($por_desarrollador, 'Desarrollador', 3);
    $por_actividad_html = ConstruirBarrasTop($por_actividad, 'Tipo_Actividad', 4);

    $detalle_html = ConstruirTablaDetalle($detalle);

    $content = '<page backtop="0mm" backbottom="0mm">' .
        '<div class="page-content">' .
        $style .
        $cabecera .
        ($filtros != '' ? '<div class="small" style="margin-top:4px;">Filtros:' . $filtros . '</div>' : '') .
        $resumen_ejecutivo .
        '<div class="section-title">Tiempo de entrega y ciclo promedio (horas)</div>' .
        $tiempos_promedio_html .
        '<div class="section-title">Estado del flujo de trabajo</div>' .
        $pie_html .
        '<div class="small" style="margin-top:4px;">Distribución porcentual del estado actual de las solicitudes.</div>' .
        '<div class="section-title">Estado del flujo de trabajo (tabla)</div>' .
        $resumen_html .
        '<div class="section-title">Distribución principal</div>' .
        '<table style="width:100%"><tr><td style="width:50%;vertical-align:top;">' .
            '<div class="small" style="margin-bottom:4px;">Top áreas</div>' . $por_area_html .
            '<div class="small" style="margin:8px 0 4px 0;">Top módulos</div>' . $por_modulo_html .
        '</td><td style="width:50%;vertical-align:top;">' .
            '<div class="small" style="margin-bottom:4px;">Top tipos</div>' . $por_tipo_html .
            '<div class="small" style="margin:8px 0 4px 0;">Top desarrolladores</div>' . $por_desarrollador_html .
        '</td></tr></table>' .
        '<div class="section-title">Actividad del mes</div>' .
        $por_actividad_html .
        '<div class="section-title">Detalle de solicitudes</div>' . $detalle_html .
        '</div>' .
    '</page>';

    return $content;
}

function ConstruirTablaTotales($rows, $campo) {
    $html = '<table cellspacing="0" cellpadding="0" style="width:100%; border-collapse:collapse;">';
    $html .= '<tr>';
    $html .= '<td class="table-header">' . $campo . '</td>';
    $html .= '<td class="table-header">Total</td>';
    $html .= '</tr>';

    if (count($rows) > 0) {
        foreach ($rows as $row) {
            $nombre = ($row[$campo] == '' || $row[$campo] == null) ? 'Sin dato' : $row[$campo];
            $html .= '<tr>';
            $html .= '<td class="table-cell">' . $nombre . '</td>';
            $html .= '<td class="table-cell table-cell-center">' . (int) $row['Total'] . '</td>';
            $html .= '</tr>';
        }
    } else {
        $html .= '<tr><td class="table-cell" colspan="2" style="text-align:center;">Sin registros</td></tr>';
    }

    $html .= '</table>';
    return $html;
}

function ConstruirResumenTabla($resumen) {
    $html =
        '<table cellspacing="0" cellpadding="0" style="width:100%; border-collapse:collapse; margin-top:6px;">' .
            '<tr>' .
                '<td class="table-header">Total</td>' .
                '<td class="table-header">Recibidas</td>' .
                '<td class="table-header">Aprobadas</td>' .
                '<td class="table-header">En Desarrollo</td>' .
                '<td class="table-header">Realizadas</td>' .
                '<td class="table-header">Finalizadas</td>' .
                '<td class="table-header">Trabajo en curso (WIP)</td>' .
                '<td class="table-header">Rechazadas</td>' .
                '<td class="table-header">Devueltas</td>' .
            '</tr>' .
            '<tr>' .
                '<td class="table-cell table-cell-center">' . (int) $resumen['Total'] . '</td>' .
                '<td class="table-cell table-cell-center">' . (int) $resumen['Recibidas'] . '</td>' .
                '<td class="table-cell table-cell-center">' . (int) $resumen['Aprobadas'] . '</td>' .
                '<td class="table-cell table-cell-center">' . (int) $resumen['En_Desarrollo'] . '</td>' .
                '<td class="table-cell table-cell-center">' . (int) $resumen['Realizadas'] . '</td>' .
                '<td class="table-cell table-cell-center">' . (int) $resumen['Finalizadas'] . '</td>' .
                '<td class="table-cell table-cell-center">' . (int) $resumen['WIP'] . '</td>' .
                '<td class="table-cell table-cell-center">' . (int) $resumen['Rechazadas'] . '</td>' .
                '<td class="table-cell table-cell-center">' . (int) $resumen['Devueltas'] . '</td>' .
            '</tr>' .
        '</table>';
    return $html;
}

function ConstruirTiemposPromedio($medianas_solicitud, $tiempos_dev_act) {
    $html = '<table cellspacing="0" cellpadding="0" style="width:100%; border-collapse:collapse; margin-top:6px;">';
    $html .= '<tr>' .
        '<td class="table-header">Mediana entrega (P50)</td>' .
        '<td class="table-header">Mediana desarrollo (P50)</td>' .
        '<td class="table-header">Mediana aprobación (P50)</td>' .
        '</tr>';
    $html .= '<tr>' .
        '<td class="table-cell table-cell-center">' . number_format((float) $medianas_solicitud['entrega_p50'], 2) . ' h</td>' .
        '<td class="table-cell table-cell-center">' . number_format((float) $tiempos_dev_act['p50'], 2) . ' h</td>' .
        '<td class="table-cell table-cell-center">' . number_format((float) $medianas_solicitud['aprobacion_p50'], 2) . ' h</td>' .
        '</tr>';
    $html .= '</table>';
    $html .= '<div class="small" style="margin-top:4px;">Medianas calculadas en horas. Desarrollo basado en actividades (Inicio → Fin). N=' . (int) $tiempos_dev_act['count'] . '.</div>';
    return $html;
}

function ConstruirBarrasTop($rows, $campo, $max_items) {
    $html = '<table style="width:100%;">';
    if (count($rows) == 0) {
        $html .= '<tr><td class="small">Sin registros</td></tr></table>';
        return $html;
    }

    $max = 1;
    foreach ($rows as $r) {
        $max = max($max, (int) $r['Total']);
    }

    $count = 0;
    foreach ($rows as $r) {
        if ($count >= $max_items) { break; }
        $nombre = ($r[$campo] == '' || $r[$campo] == null) ? 'Sin dato' : $r[$campo];
        $width = intval(((int) $r['Total'] / $max) * 220);
        $html .= '<tr>';
        $html .= '<td style="width:120px;" class="bar-label">' . $nombre . '</td>';
        $html .= '<td style="width:240px;"><div style="height:7px;background:#3b82f6;width:' . $width . 'px;"></div></td>';
        $html .= '<td style="width:30px;text-align:right;font-size:8px;">' . (int) $r['Total'] . '</td>';
        $html .= '</tr>';
        $count++;
    }
    $html .= '</table>';
    return $html;
}

function ObtenerCortesCfd($fecha_ini, $fecha_fin) {
    $inicio = new DateTime(substr($fecha_ini, 0, 10));
    $fin = new DateTime(substr($fecha_fin, 0, 10));
    $cortes = array();

    while ($inicio <= $fin) {
        if ($inicio->format('N') == '7') {
            $cortes[] = $inicio->format('Y-m-d');
        }
        $inicio->modify('+1 day');
    }

    $ultimo = $fin->format('Y-m-d');
    if (count($cortes) == 0 || $cortes[count($cortes) - 1] != $ultimo) {
        $cortes[] = $ultimo;
    }
    return $cortes;
}

function ObtenerDatosCfdDesdeActividad($queryObj, $cortes) {
    $mapa = array(
        'Creacion' => 'Recibida',
        'Aprobacion Proh' => 'Aprobada',
        'Asignacion Desarrollador' => 'Aprobada',
        'Inicio Desarrollo' => 'En Desarrollo',
        'Fin Desarrollo' => 'Realizada',
        'Finalizacion' => 'Finalizada',
        'Rechazo' => 'Rechazada',
        'Devolucion' => 'Devuelto',
        'Incidencia' => 'Devuelto'
    );

    $series = array();
    foreach ($cortes as $corte) {
        $query = "
            SELECT ASS.Id_Solicitud_Sigespro, ASS.Tipo_Actividad
            FROM Actividad_Solicitud_Sigespro ASS
            INNER JOIN (
                SELECT Id_Solicitud_Sigespro, MAX(Fecha_Actividad) AS MaxFecha
                FROM Actividad_Solicitud_Sigespro
                WHERE Fecha_Actividad <= '$corte 23:59:59'
                GROUP BY Id_Solicitud_Sigespro
            ) X ON X.Id_Solicitud_Sigespro = ASS.Id_Solicitud_Sigespro
             AND X.MaxFecha = ASS.Fecha_Actividad
        ";
        $queryObj->SetQuery($query);
        $rows = $queryObj->ExecuteQuery('multiple');
        $data = array();
        foreach ($rows as $r) {
            $tipo = $r['Tipo_Actividad'];
            $estado = isset($mapa[$tipo]) ? $mapa[$tipo] : 'Recibida';
            if (!isset($data[$estado])) {
                $data[$estado] = 0;
            }
            $data[$estado] += 1;
        }
        $series[$corte] = $data;
    }
    return $series;
}

function ConstruirCfdGrafico($cortes, $series) {
    $colores = array(
        'Recibida' => '#94a3b8',
        'Aprobada' => '#60a5fa',
        'En Desarrollo' => '#f59e0b',
        'Realizada' => '#34d399',
        'Finalizada' => '#10b981',
        'Rechazada' => '#ef4444',
        'Devuelto' => '#f97316'
    );

    $max_total = 1;
    foreach ($series as $data) {
        $total = 0;
        foreach ($data as $v) { $total += $v; }
        $max_total = max($max_total, $total);
    }

    $legend = '<table style="width:100%;margin-bottom:4px;"><tr>';
    $cols = 0;
    foreach ($colores as $estado => $color) {
        $legend .= '<td style="width:10px;"><div style="width:8px;height:8px;background:' . $color . ';"></div></td>' .
            '<td class="small" style="width:85px;">' . $estado . '</td>';
        $cols++;
        if ($cols == 3) {
            $legend .= '</tr><tr>';
            $cols = 0;
        }
    }
    $legend .= '</tr></table>';

    $html = $legend . '<table style="width:100%;">';
    foreach ($cortes as $corte) {
        $data = isset($series[$corte]) ? $series[$corte] : array();
        $html .= '<tr>';
        $html .= '<td style="width:70px;font-size:8px;">' . $corte . '</td>';
        $html .= '<td style="width:420px;">';
        $html .= '<div style="height:10px;width:420px;border:1px solid #e5e7eb;">';
        $acum_width = 0;
        foreach ($colores as $estado => $color) {
            $valor = isset($data[$estado]) ? $data[$estado] : 0;
            $width = intval(($valor / $max_total) * 420);
            if ($width > 0) {
                $html .= '<div style="float:left;height:10px;background:' . $color . ';width:' . $width . 'px;"></div>';
                $acum_width += $width;
            }
        }
        if ($acum_width < 420) {
            $html .= '<div style="float:left;height:10px;background:#f3f4f6;width:' . (420 - $acum_width) . 'px;"></div>';
        }
        $html .= '</div>';
        $html .= '</td>';
        $total = 0;
        foreach ($data as $v) { $total += $v; }
        $html .= '<td style="width:40px;font-size:8px;text-align:right;">' . $total . '</td>';
        $html .= '</tr>';
    }
    $html .= '</table>';
    $html .= '<div class="small" style="margin-top:4px;">Valores corresponden al total acumulado por corte.</div>';

    $ultimo_corte = $cortes[count($cortes) - 1];
    $ultimo_data = isset($series[$ultimo_corte]) ? $series[$ultimo_corte] : array();
    $resumen = '<table style="width:100%;margin-top:6px;">';
    $resumen .= '<tr><td class="table-header">Estado</td><td class="table-header">Total último corte (' . $ultimo_corte . ')</td></tr>';
    foreach ($colores as $estado => $color) {
        $valor = isset($ultimo_data[$estado]) ? $ultimo_data[$estado] : 0;
        $resumen .= '<tr>' .
            '<td class="table-cell"><span style="display:inline-block;width:8px;height:8px;background:' . $color . ';margin-right:4px;"></span>' . $estado . '</td>' .
            '<td class="table-cell table-cell-center">' . $valor . '</td>' .
            '</tr>';
    }
    $resumen .= '</table>';

    return $html . $resumen;
}

function ConstruirBarraEstados($resumen) {
    $data = array(
        'Recibida' => (int) $resumen['Recibidas'],
        'Aprobada' => (int) $resumen['Aprobadas'],
        'En Desarrollo' => (int) $resumen['En_Desarrollo'],
        'Realizada' => (int) $resumen['Realizadas'],
        'Finalizada' => (int) $resumen['Finalizadas'],
        'Rechazada' => (int) $resumen['Rechazadas'],
        'Devuelto' => (int) $resumen['Devueltas']
    );
    $colores = array(
        'Recibida' => '#94a3b8',
        'Aprobada' => '#60a5fa',
        'En Desarrollo' => '#f59e0b',
        'Realizada' => '#34d399',
        'Finalizada' => '#10b981',
        'Rechazada' => '#ef4444',
        'Devuelto' => '#f97316'
    );

    $total = array_sum($data);
    if ($total <= 0) {
        return '<div class="small">Sin datos para grafica por estados</div>';
    }

    $barra = '<div style="height:12px;width:420px;border:1px solid #e5e7eb;">';
    $acum = 0;
    foreach ($data as $label => $value) {
        if ($value <= 0) { continue; }
        $width = intval(($value / $total) * 420);
        $barra .= '<div style="float:left;height:12px;background:' . $colores[$label] . ';width:' . $width . 'px;"></div>';
        $acum += $width;
    }
    if ($acum < 420) {
        $barra .= '<div style="float:left;height:12px;background:#f3f4f6;width:' . (420 - $acum) . 'px;"></div>';
    }
    $barra .= '</div>';

    $items = array();
    foreach ($data as $label => $value) {
        if ($value <= 0) { continue; }
        $porcentaje = ($total > 0) ? round(($value / $total) * 100, 1) : 0;
        $items[] =
            '<td style="width:10px;"><div style="width:8px;height:8px;background:' . $colores[$label] . ';"></div></td>' .
            '<td class="small" style="width:90px;">' . $label . '</td>' .
            '<td class="small" style="width:60px;text-align:right;">' . $value . ' | ' . $porcentaje . '%</td>';
    }

    $legend = '<table style="width:100%;margin-top:4px;"><tr>';
    $cols = 0;
    foreach ($items as $item) {
        $legend .= $item;
        $cols++;
        if ($cols == 3) {
            $legend .= '</tr><tr>';
            $cols = 0;
        }
    }
    $legend .= '</tr></table>';

    return '<div>' . $barra . $legend . '</div>';
}

function ConstruirResumenEjecutivo($resumen) {
    $total = (int) $resumen['Total'];
    $finalizadas = (int) $resumen['Finalizadas'];
    $wip = (int) $resumen['WIP'];

    $texto = 'Durante el periodo analizado se recibieron ' . $total . ' solicitudes. ';
    $texto .= 'El equipo finalizó ' . $finalizadas . ', con trabajo en curso (WIP) de ' . $wip . ' solicitudes. ';
    if ($wip > $finalizadas) {
        $texto .= 'La carga en curso es mayor que el ritmo de cierre, lo que sugiere acumulación operativa.';
    } else {
        $texto .= 'El ritmo de cierre es estable frente a la carga en curso.';
    }
    return '<div style="border:1px solid #e5e7eb;background:#f9fafb;padding:10px;font-size:10px;line-height:14px;">' .
        '<strong>Resumen ejecutivo</strong><br>' . $texto .
        '</div>';
}

function ConstruirLecturaCfd($resumen) {
    $wip = (int) $resumen['WIP'];
    $finalizadas = (int) $resumen['Finalizadas'];
    if ($wip > $finalizadas) {
        return 'El CFD muestra acumulación en estados intermedios frente a las finalizadas, indicando posible cuello de botella.';
    }
    return 'El CFD sugiere estabilidad entre trabajo en curso y finalizaciones.';
}

function ConstruirTablaDetalle($detalle) {
    $html = '<table cellspacing="0" cellpadding="0" style="width:100%; border-collapse:collapse; margin-top:6px;">';
    $html .= '<tr>';
    $html .= '<td class="table-header" style="font-size:10px;padding:8px;">ID</td>';
    $html .= '<td class="table-header" style="font-size:10px;padding:8px;">Fecha</td>';
    $html .= '<td class="table-header" style="font-size:10px;padding:8px;">Área</td>';
    $html .= '<td class="table-header" style="font-size:10px;padding:8px;">Módulo</td>';
    $html .= '<td class="table-header" style="font-size:10px;padding:8px;">Tipo</td>';
    $html .= '<td class="table-header" style="font-size:10px;padding:8px;">Estado</td>';
    $html .= '<td class="table-header" style="font-size:10px;padding:8px;">Desarrollador</td>';
    $html .= '</tr>';

    if (count($detalle) > 0) {
        foreach ($detalle as $row) {
            $html .= '<tr>';
            $html .= '<td class="table-cell table-cell-center" style="font-size:9px;padding:6px;">' . $row['Id_Solicitud_Sigespro'] . '</td>';
            $html .= '<td class="table-cell table-cell-center" style="font-size:9px;padding:6px;">' . $row['Fecha_Solicitud'] . '</td>';
            $html .= '<td class="table-cell" style="font-size:9px;padding:6px;">' . $row['Area_Sistema'] . '</td>';
            $html .= '<td class="table-cell" style="font-size:9px;padding:6px;">' . $row['Modulo_Sistema'] . '</td>';
            $html .= '<td class="table-cell" style="font-size:9px;padding:6px;">' . $row['Tipo_Solicitud'] . '</td>';
            $html .= '<td class="table-cell table-cell-center" style="font-size:9px;padding:6px;">' . $row['Estado_Solicitud'] . '</td>';
            $html .= '<td class="table-cell" style="font-size:9px;padding:6px;">' . $row['Desarrollador'] . '</td>';
            $html .= '</tr>';
        }
    } else {
        $html .= '<tr><td class="table-cell" colspan="7" style="text-align:center;font-size:9px;padding:6px;">Sin registros</td></tr>';
    }

    $html .= '</table>';
    return $html;
}
?>
