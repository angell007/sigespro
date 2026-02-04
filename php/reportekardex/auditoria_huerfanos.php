<?php
ini_set('max_execution_time', 3600);
ini_set('memory_limit', '512M');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: text/html; charset=UTF-8');

require_once('../../config/start.inc.php');
include_once('../../class/class.consulta.php');

$id_punto = isset($_GET['id_punto']) ? (int) $_GET['id_punto'] : 0;
$fecha_inicio = isset($_GET['fecha_inicio']) && $_GET['fecha_inicio'] !== '' ? $_GET['fecha_inicio'] : '2000-01-01';
$fecha_fin = isset($_GET['fecha_fin']) && $_GET['fecha_fin'] !== '' ? $_GET['fecha_fin'] : date('Y-m-d');
$solo_diferencias = isset($_GET['solo_diferencias']) ? (int) $_GET['solo_diferencias'] : 0;
$export = isset($_GET['export']) ? $_GET['export'] : '';

if ($id_punto <= 0) {
    echo '<h2>Falta el parámetro id_punto</h2>';
    exit;
}

function h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function getSaldoKardexPunto($idPunto, $idProducto, $lote, $fecha_inicio, $fecha_fin)
{
    $lote_safe = addslashes($lote);
    $condicion = " AND R.Id_Origen=$idPunto AND R.Tipo_Origen='Punto_Dispensacion' AND R.Fecha BETWEEN '$fecha_inicio 00:00:00' AND '$fecha_fin 23:59:59'";
    $condicion3 = " AND AI.Id_Origen_Destino=$idPunto AND AI.Origen_Destino='Punto'";
    $condicion2 = " AND AR.Id_Punto_Dispensacion=$idPunto AND AR.Fecha BETWEEN '$fecha_inicio 00:00:00' AND '$fecha_fin 23:59:59'";

    $ultimo_dia_mes = date("Y-m-d", (mktime(0, 0, 0, date("m", strtotime($fecha_inicio)), 1, date("Y", strtotime($fecha_inicio))) - 1));

    $query_inicial = "SELECT SUM(Cantidad) as Total
        FROM Saldo_Inicial_Kardex
        WHERE Id_Producto = $idProducto
        AND Lote = '$lote_safe'
        AND Fecha = '$ultimo_dia_mes'
        AND Id_Punto_Dispensacion = $idPunto
        GROUP BY Id_Producto";

    $oCon = new consulta();
    $oCon->setQuery($query_inicial);
    $res = $oCon->getData();
    unset($oCon);

    $acum = (int) ($res['Total'] ?? 0);

    $query = '
    (SELECT "Salida" as Tipo, PR.Cantidad, R.Fecha as Fecha
     FROM Producto_Remision PR
     INNER JOIN Remision R ON R.Id_Remision = PR.Id_Remision
     WHERE R.Estado = "Anulada" AND PR.Id_Producto = ' . $idProducto . ' AND PR.Lote = "' . $lote_safe . '"' . $condicion . ')

    UNION ALL (SELECT
        (CASE R.Estado WHEN "Anulada" THEN "Entrada" ELSE "Salida" END) as Tipo,
        PR.Cantidad,
        R.Fecha as Fecha
     FROM Producto_Remision PR
     INNER JOIN Remision R ON R.Id_Remision = PR.Id_Remision
     WHERE PR.Id_Producto = ' . $idProducto . ' AND PR.Lote = "' . $lote_safe . '"' . $condicion . ')

    UNION ALL (SELECT "Salida" as Tipo, PR.Cantidad, R.Fecha as Fecha
     FROM Producto_Remision_Antigua PR
     INNER JOIN Remision_Antigua R ON R.Id_Remision = PR.Id_Remision
     WHERE PR.Id_Producto = ' . $idProducto . ' AND PR.Lote = "' . $lote_safe . '"' . $condicion . ')

    UNION ALL (SELECT "Entrada" as Tipo, PAR.Cantidad, AR.Fecha as Fecha
     FROM Producto_Acta_Recepcion_Remision PAR
     INNER JOIN Acta_Recepcion_Remision AR ON PAR.Id_Acta_Recepcion_Remision = AR.Id_Acta_Recepcion_Remision
     WHERE PAR.Id_Producto = ' . $idProducto . ' AND PAR.Lote = "' . $lote_safe . '"' . $condicion2 . ' AND AR.Estado = "Aprobada")

    UNION ALL (SELECT "Entrada" as Tipo, PAR.Cantidad, AR.Fecha_Creacion as Fecha
     FROM Producto_Acta_Recepcion PAR
     INNER JOIN Acta_Recepcion AR ON PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion
     WHERE PAR.Id_Producto = ' . $idProducto . ' AND PAR.Lote = "' . $lote_safe . '" AND AR.Id_Punto_Dispensacion = ' . $idPunto . '
     AND AR.Estado = "Aprobada" AND (AR.Fecha_Creacion BETWEEN "' . $fecha_inicio . '" AND "' . $fecha_fin . '"))

    UNION ALL (SELECT
        (CASE AI.Estado WHEN "Anulada" THEN IF(AI.Tipo="Entrada","Salida","Entrada") ELSE AI.Tipo END) as Tipo,
        PAI.Cantidad,
        AI.Fecha as Fecha
     FROM Producto_Ajuste_Individual PAI
     INNER JOIN Ajuste_Individual AI ON AI.Id_Ajuste_Individual = PAI.Id_Ajuste_Individual
     WHERE PAI.Id_Producto = ' . $idProducto . ' AND PAI.Lote = "' . $lote_safe . '"' . $condicion3 . '
     AND (AI.Fecha BETWEEN "' . $fecha_inicio . ' 00:00:00" AND "' . $fecha_fin . ' 23:59:59"))

    UNION ALL (SELECT "Inventario" as Tipo, SUM(PIF.Cantidad_Final) as Cantidad, INF.Fecha_Fin as Fecha
     FROM Producto_Inventario_Fisico_Punto PIF
     INNER JOIN Inventario_Fisico_Punto INF ON PIF.Id_Inventario_Fisico_Punto=INF.Id_Inventario_Fisico_Punto
     WHERE INF.Estado="Terminado" AND PIF.Id_Producto = ' . $idProducto . ' AND PIF.Lote = "' . $lote_safe . '"
     AND INF.Id_Punto_Dispensacion = ' . $idPunto . ' AND INF.Fecha_Inicio BETWEEN "' . $fecha_inicio . ' 00:00:00" AND "' . $fecha_fin . ' 23:59:59"
     GROUP BY PIF.Id_Inventario_Fisico_Punto)

    UNION ALL (SELECT "Salida" as Tipo, PD.Cantidad_Entregada as Cantidad,
        IFNULL((SELECT PDP.Timestamp FROM Producto_Dispensacion_Pendiente PDP WHERE PDP.Id_Producto_Dispensacion=PD.Id_Producto_Dispensacion LIMIT 1),
        IFNULL((SELECT PD2.Fecha_Carga FROM Producto_Dispensacion PD2 WHERE PD2.Id_Producto_Dispensacion=PD.Id_Producto_Dispensacion LIMIT 1),D.Fecha_Actual)) AS Fecha
     FROM Producto_Dispensacion PD
     INNER JOIN Dispensacion D ON PD.Id_Dispensacion=D.Id_Dispensacion
     INNER JOIN Inventario_Nuevo I ON PD.Id_Inventario_Nuevo=I.Id_Inventario_Nuevo
     WHERE D.Estado_Dispensacion = "Anulada" AND PD.Id_Producto = ' . $idProducto . ' AND PD.Lote = "' . $lote_safe . '"
     AND PD.Cantidad_Entregada!=0 AND I.Id_Punto_Dispensacion = ' . $idPunto . '
     HAVING Fecha BETWEEN "' . $fecha_inicio . ' 00:00:00" AND "' . $fecha_fin . ' 23:59:59")

    UNION ALL (SELECT
        (CASE D.Estado_Dispensacion WHEN "Anulada" THEN "Entrada" ELSE "Salida" END) AS Tipo,
        PD.Cantidad_Entregada as Cantidad,
        IFNULL((SELECT PDP.Timestamp FROM Producto_Dispensacion_Pendiente PDP WHERE PDP.Id_Producto_Dispensacion=PD.Id_Producto_Dispensacion LIMIT 1),
        IFNULL((SELECT PD2.Fecha_Carga FROM Producto_Dispensacion PD2 WHERE PD2.Id_Producto_Dispensacion=PD.Id_Producto_Dispensacion LIMIT 1),D.Fecha_Actual)) AS Fecha
     FROM Producto_Dispensacion PD
     INNER JOIN Dispensacion D ON PD.Id_Dispensacion=D.Id_Dispensacion
     INNER JOIN Inventario_Nuevo I ON PD.Id_Inventario_Nuevo=I.Id_Inventario_Nuevo
     WHERE PD.Id_Producto = ' . $idProducto . ' AND PD.Lote = "' . $lote_safe . '" AND PD.Cantidad_Entregada!=0
     AND I.Id_Punto_Dispensacion = ' . $idPunto . '
     HAVING Fecha BETWEEN "' . $fecha_inicio . ' 00:00:00" AND "' . $fecha_fin . ' 23:59:59")

    ORDER BY Fecha ASC';

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $resultados = $oCon->getData();
    unset($oCon);

    $prev_tipo = '';
    $prev_fecha = '';
    foreach ($resultados as $res) {
        $tipo = $res['Tipo'];
        $cantidad = (float) $res['Cantidad'];
        $fecha = $res['Fecha'];

        if ($tipo === 'Entrada') {
            $acum += $cantidad;
        } elseif ($tipo === 'Salida') {
            $acum -= $cantidad;
        } elseif ($tipo === 'Inventario') {
            $fecha_ant = $prev_fecha ? date('Y-m-d', strtotime($prev_fecha)) : '';
            $fecha_act = date('Y-m-d', strtotime($fecha));
            if ($prev_tipo !== 'Inventario' || $fecha_ant !== $fecha_act) {
                $acum = $cantidad;
            } else {
                $acum += $cantidad;
            }
        }

        $prev_tipo = $tipo;
        $prev_fecha = $fecha;
    }

    return $acum;
}

$query_orphans = "SELECT
    I.Id_Inventario_Nuevo,
    I.Id_Producto,
    P.Nombre_Comercial AS Producto,
    I.Codigo_CUM,
    I.Lote,
    I.Fecha_Vencimiento,
    I.Cantidad,
    I.Cantidad_Apartada,
    I.Cantidad_Seleccionada,
    (I.Cantidad - IFNULL(I.Cantidad_Apartada,0) - IFNULL(I.Cantidad_Seleccionada,0)) AS Disponible,
    I.Id_Estiba,
    I.Id_Punto_Dispensacion,
    I.Fecha_Carga,
    I.Identificacion_Funcionario
  FROM Inventario_Nuevo I
  INNER JOIN Producto P ON P.Id_Producto = I.Id_Producto
  WHERE I.Id_Punto_Dispensacion = $id_punto
    AND (I.Id_Estiba IS NULL OR I.Id_Estiba = 0)
  ORDER BY I.Fecha_Carga DESC";

$oCon = new consulta();
$oCon->setQuery($query_orphans);
$oCon->setTipo('Multiple');
$registros = $oCon->getData();
unset($oCon);

$total_registros = count($registros);
$total_cantidad = 0;
$total_diferencias = 0;

$cache_saldo = [];

foreach ($registros as $idx => $reg) {
    $total_cantidad += (float) $reg['Cantidad'];
}

if ($export === '1' || $export === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="auditoria_huerfanos_' . $id_punto . '_' . $fecha_inicio . '_' . $fecha_fin . '.csv"');

    $out = fopen('php://output', 'w');
    fputcsv($out, [
        'Inventario',
        'Id_Producto',
        'Producto',
        'CUM',
        'Lote',
        'Fecha_Vencimiento',
        'Cantidad_Actual',
        'Cantidad_Apartada',
        'Cantidad_Seleccionada',
        'Disponible',
        'Saldo_Kardex',
        'Diferencia',
        'Id_Estiba',
        'Id_Punto',
        'Fecha_Carga',
        'Identificacion_Funcionario'
    ]);

    foreach ($registros as $reg) {
        $cache_key = $reg['Id_Producto'] . '|' . $reg['Lote'];
        if (!isset($cache_saldo[$cache_key])) {
            $cache_saldo[$cache_key] = getSaldoKardexPunto(
                (int) $id_punto,
                (int) $reg['Id_Producto'],
                $reg['Lote'],
                $fecha_inicio,
                $fecha_fin
            );
        }

        $saldo_kardex = $cache_saldo[$cache_key];
        $diferencia = $saldo_kardex - (float) $reg['Cantidad'];

        if ($solo_diferencias && abs($diferencia) < 0.0001) {
            continue;
        }

        fputcsv($out, [
            $reg['Id_Inventario_Nuevo'],
            $reg['Id_Producto'],
            $reg['Producto'],
            $reg['Codigo_CUM'],
            $reg['Lote'],
            $reg['Fecha_Vencimiento'],
            $reg['Cantidad'],
            $reg['Cantidad_Apartada'],
            $reg['Cantidad_Seleccionada'],
            $reg['Disponible'],
            $saldo_kardex,
            $diferencia,
            $reg['Id_Estiba'],
            $reg['Id_Punto_Dispensacion'],
            $reg['Fecha_Carga'],
            $reg['Identificacion_Funcionario'],
        ]);
    }

    fclose($out);
    exit;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Auditoría Huérfanos - Inventario</title>
    <style>
        :root {
            --bg: #0b0f14;
            --card: #121a22;
            --muted: #8da2b5;
            --text: #e7f0f7;
            --accent: #5dd6ff;
            --accent-2: #64f7b7;
            --danger: #ff6b6b;
            --warn: #ffd166;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Space Grotesk", "Segoe UI", Tahoma, sans-serif;
            background: radial-gradient(1200px 700px at 10% -20%, #1a2736 0%, transparent 70%),
                        radial-gradient(1000px 800px at 100% 0%, #0f2b2f 0%, transparent 70%),
                        var(--bg);
            color: var(--text);
        }
        header {
            padding: 24px 24px 10px;
        }
        h1 {
            margin: 0 0 6px;
            font-size: 26px;
            letter-spacing: 0.4px;
        }
        .sub {
            color: var(--muted);
            font-size: 14px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
            padding: 16px 24px 24px;
        }
        .card {
            background: linear-gradient(180deg, rgba(255,255,255,0.04), rgba(255,255,255,0.01));
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 14px;
            padding: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.25);
        }
        .card h3 {
            margin: 0 0 6px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--muted);
        }
        .card .value {
            font-size: 22px;
            font-weight: 600;
        }
        .table-wrap {
            margin: 0 24px 32px;
            background: var(--card);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px;
            overflow: hidden;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead th {
            position: sticky;
            top: 0;
            background: #0f141a;
            color: var(--muted);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 12px 10px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        tbody td {
            padding: 10px;
            font-size: 13px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        tbody tr:nth-child(2n) { background: rgba(255,255,255,0.02); }
        tbody tr.diff { background: rgba(255,107,107,0.12); }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 11px;
            background: rgba(255,255,255,0.08);
        }
        .badge.ok { background: rgba(100,247,183,0.18); color: var(--accent-2); }
        .badge.warn { background: rgba(255,209,102,0.18); color: var(--warn); }
        .badge.danger { background: rgba(255,107,107,0.18); color: var(--danger); }
        .muted { color: var(--muted); }
        .footer-note {
            padding: 0 24px 24px;
            font-size: 12px;
            color: var(--muted);
        }
    </style>
</head>
<body>
    <header>
        <h1>Auditoría de Inventarios Huérfanos</h1>
        <div class="sub">Punto de dispensación: <?php echo h($id_punto); ?> · Rango Kardex: <?php echo h($fecha_inicio); ?> → <?php echo h($fecha_fin); ?></div>
    </header>

    <section class="grid">
        <div class="card">
            <h3>Huérfanos</h3>
            <div class="value"><?php echo h($total_registros); ?></div>
        </div>
        <div class="card">
            <h3>Cantidad total</h3>
            <div class="value"><?php echo h($total_cantidad); ?></div>
        </div>
        <div class="card">
            <h3>Solo diferencias</h3>
            <div class="value"><?php echo $solo_diferencias ? 'Sí' : 'No'; ?></div>
        </div>
    </section>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Inventario</th>
                    <th>Producto</th>
                    <th>CUM</th>
                    <th>Lote</th>
                    <th>Vence</th>
                    <th>Actual</th>
                    <th>Disponible</th>
                    <th>Saldo Kardex</th>
                    <th>Diferencia</th>
                    <th>Estiba</th>
                    <th>Fecha Carga</th>
                </tr>
            </thead>
            <tbody>
<?php
$row_num = 0;
foreach ($registros as $reg) {
    $row_num++;
    $cache_key = $reg['Id_Producto'] . '|' . $reg['Lote'];
    if (!isset($cache_saldo[$cache_key])) {
        $cache_saldo[$cache_key] = getSaldoKardexPunto(
            (int) $id_punto,
            (int) $reg['Id_Producto'],
            $reg['Lote'],
            $fecha_inicio,
            $fecha_fin
        );
    }

    $saldo_kardex = $cache_saldo[$cache_key];
    $diferencia = $saldo_kardex - (float) $reg['Cantidad'];
    $total_diferencias += $diferencia;

    if ($solo_diferencias && abs($diferencia) < 0.0001) {
        continue;
    }

    $row_class = abs($diferencia) >= 0.0001 ? 'diff' : '';
    $badge = abs($diferencia) < 0.0001 ? '<span class="badge ok">OK</span>' : '<span class="badge danger">DIF</span>';

    echo '<tr class="' . $row_class . '">
        <td>' . h($row_num) . '</td>
        <td>' . h($reg['Id_Inventario_Nuevo']) . '</td>
        <td>' . h($reg['Producto']) . '</td>
        <td>' . h($reg['Codigo_CUM']) . '</td>
        <td>' . h($reg['Lote']) . '</td>
        <td>' . h($reg['Fecha_Vencimiento']) . '</td>
        <td>' . h($reg['Cantidad']) . '</td>
        <td>' . h($reg['Disponible']) . '</td>
        <td>' . h($saldo_kardex) . '</td>
        <td>' . h($diferencia) . ' ' . $badge . '</td>
        <td>' . h($reg['Id_Estiba']) . '</td>
        <td class="muted">' . h($reg['Fecha_Carga']) . '</td>
    </tr>';
}
?>
            </tbody>
        </table>
    </div>

    <div class="footer-note">
        Total diferencia acumulada (Kardex - Actual): <?php echo h($total_diferencias); ?>
    </div>
</body>
</html>
