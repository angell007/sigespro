<?php
// DEBUG: Muestra errores en pantalla:
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
set_time_limit(0);

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');
require_once('../../../class/html2pdf.class.php');

/** ========================================================================================
 *  Sección de variables $_REQUEST
 * ======================================================================================== */
$tipo          = isset($_REQUEST['tipo'])          ? $_REQUEST['tipo']          : '';
$fecha_ini     = isset($_REQUEST['fecha_ini'])     ? $_REQUEST['fecha_ini']     : '';
$fecha_fin     = isset($_REQUEST['fecha_fin'])     ? $_REQUEST['fecha_fin']     : '';
$tipo_reporte  = isset($_REQUEST['tipo_reporte'])  ? $_REQUEST['tipo_reporte']  : '';
$nivel_reporte = isset($_REQUEST['nivel'])         ? $_REQUEST['nivel']         : '';
$cta_ini       = isset($_REQUEST['cta_ini'])       ? $_REQUEST['cta_ini']       : '';
$cta_fin       = isset($_REQUEST['cta_fin'])       ? $_REQUEST['cta_fin']       : '';
$id_centro_costo = isset($_REQUEST['centro_costo']) ? $_REQUEST['centro_costo'] : '';

/** ========================================================================================
 *  Función básica para mostrar fecha en formato d/m/Y
 * ======================================================================================== */
function fecha($str)
{
    $parts = explode(' ', $str);
    $date  = explode('-', $parts[0]);
    return $date[2] . '/' . $date[1] . '/' . $date[0];
}

/** ========================================================================================
 *  Obtener último día del mes. (Aquí se dejó forzado a '2018-12-31' según la lógica interna).
 * ======================================================================================== */
function getUltimoDiaMes($fecha_inicio)
{
    // Originalmente calculaba: date("Y-m-d",(mktime(0,0,0,date("m",strtotime($fecha_inicio)),1,date("Y",strtotime($fecha_inicio)))-1));
    // Pero se forzó a 2018-12-31
    return '2018-12-31';
}

$ultimo_dia_mes = getUltimoDiaMes($fecha_ini);

/** ========================================================================================
 *  Identificar centro de costo, si aplica
 * ======================================================================================== */
$centro_costo = false;
if ($id_centro_costo) {
    $oItem        = new complex('Centro_Costo', 'Id_Centro_Costo', $id_centro_costo);
    $centro_costo = $oItem->getData();
    unset($oItem);
}

/** ========================================================================================
 *  Construir cond_exluir con planes a excluir
 * ======================================================================================== */
$query = "
    SELECT GROUP_CONCAT('^',Codigo_Grupo_Plan_Cuentas,'|') AS Codigos 
    FROM Excluir_Plan_Cuentas_Centro_Costo
    WHERE DATE(Excluir_Desde) <= DATE('$fecha_ini')
";
$oCon = new consulta();
$oCon->setQuery($query);
$planes_excluir = $oCon->getData();
unset($oCon);

$cond_exluir = " NOT REGEXP ' '";
if (!empty($planes_excluir['Codigos'])) {
    $temp = str_replace(',', '', $planes_excluir['Codigos']);
    $temp = substr($temp, 0, -1); // quitar último caracter
    $cond_exluir = " NOT REGEXP '$temp' ";
}

/** ========================================================================================
 *  Datos de configuración (empresa)
 * ======================================================================================== */
$oItem   = new complex('Configuracion', 'Id_Configuracion', 1);
$config  = $oItem->getData();
unset($oItem);

/** ========================================================================================
 *  getStrCondiciones() -> para filtrar PCGA / NIIF
 * ======================================================================================== */
function getStrCondiciones()
{
    global $tipo_reporte, $nivel_reporte, $cta_ini, $cta_fin, $centro_costo, $cond_exluir;
    $column = ($tipo_reporte === 'Pcga') ? 'Codigo' : 'Codigo_Niif';
    $where  = [];

    if (!empty($cta_ini)) {
        $where[] = "$column BETWEEN '$cta_ini' AND '$cta_fin'";
    }
    if (!empty($nivel_reporte)) {
        $where[] = "CHAR_LENGTH($column) BETWEEN 1 AND $nivel_reporte";
    }
    if ($centro_costo !== false) {
        // Excluir planes
        $where[] = "$column $cond_exluir";
    }
    return !empty($where) ? ' WHERE ' . implode(' AND ', $where) : '';
}

/** ========================================================================================
 *  Función para obtener saldo anterior
 * ======================================================================================== */
function obtenerSaldoAnterior($naturaleza, $datos, $index, $tipo_reporte, $nit = null, $plan = null)
{
    global $fecha_ini;

    $tipo_reporte = strtoupper($tipo_reporte);
    $campoDebito  = "Debito_$tipo_reporte";
    $campoCredito = "Credito_$tipo_reporte";

    $saldo = ($naturaleza === 'D')
        ? ($datos[$index][$campoDebito]  - $datos[$index][$campoCredito])
        : ($datos[$index][$campoCredito] - $datos[$index][$campoDebito]);

    $fechaInicio = date('Y-m-d', strtotime($fecha_ini));
    if ($fechaInicio !== '2019-01-01') {
        $fechaDesde = '2019-01-01';
        $fechaHasta = date('Y-m-d', strtotime('-1 day', strtotime($fecha_ini)));
        $movs       = getMovimientosCuenta($fechaDesde, $fechaHasta, $nit, $plan);

        $debito  = is_array($movs) ? ($movs['Debito']  ?? 0) : 0;
        $credito = is_array($movs) ? ($movs['Credito'] ?? 0) : 0;

        if ($naturaleza === 'D') {
            $saldo = ($saldo + $debito) - $credito;
        } else {
            $saldo = ($saldo + $credito) - $debito;
        }
    }
    return $saldo;
}

/** ========================================================================================
 *  Función unificada para calcular movimiento (debito o crédito)
 * ======================================================================================== */
function calcularMovimiento($codigo, $movimientos, $tipo = 'Debito')
{
    global $tipo_reporte;
    $resultados = [];

    foreach ($movimientos as $mov) {
        $mov_codigo = ($tipo_reporte === 'Pcga') ? $mov['Codigo'] : $mov['Codigo_Niif'];
        $valor      = $mov[$tipo];

        // Verificar si la cuenta del movimiento comienza con la cuenta base
        if ((substr($mov_codigo, 0, strlen($codigo)) === $codigo)) { 
            $nivel_actual = strlen($mov_codigo);
            $nivel_base   = strlen($codigo);
            $acumulador   = $mov_codigo;

            while ($nivel_actual > $nivel_base) {
                $acumulador = substr($acumulador, 0, -1);
                $nivel_actual = strlen($acumulador);
                $resultados[$acumulador] = ($resultados[$acumulador] ?? 0) + $valor;
            }
            $resultados[$mov_codigo] = ($resultados[$mov_codigo] ?? 0) + $valor;
        }
    }
    return $resultados[$codigo] ?? 0;
}

/** ========================================================================================
 *  Función para calcular el nuevo saldo según naturaleza
 * ======================================================================================== */
function calcularNuevoSaldo($naturaleza, $saldo_anterior, $debito, $credito)
{
    if ($naturaleza === 'D') {
        // Naturaleza débito => se suman débitos y se restan créditos
        return ($saldo_anterior + $debito) - $credito;
    }
    // Naturaleza crédito => se suman créditos y se restan débitos
    return ($saldo_anterior + $credito) - $debito;
}

/** ========================================================================================
 *  getMovimientosCuenta() -> para obtener movimientos del rango
 * ======================================================================================== */
function getMovimientosCuenta($fecha1, $fecha2, $nit = null, $plan = null)
{
    global $tipo_reporte, $centro_costo, $cond_exluir;

    $tipo_sufijo = ($tipo_reporte === 'Niif') ? '_Niif' : '';
    $col_debe    = "Debe$tipo_sufijo";
    $col_haber   = "Haber$tipo_sufijo";

    $whereCentroCosto = ($centro_costo !== false)
        ? "AND MC.Id_Centro_Costo = {$centro_costo['Id_Centro_Costo']} AND PC.Codigo$tipo_sufijo $cond_exluir"
        : '';

    // Diferenciamos si viene un nit (para 1 sola cuenta) o no
    if ($nit === null) {
        $query = "
            SELECT 
                MC.Id_Plan_Cuenta,
                MC.Id_Centro_Costo,
                PC.Codigo,
                PC.Nombre,
                PC.Codigo_Niif,
                PC.Nombre_Niif,
                SUM($col_debe) AS Debito,
                SUM($col_haber) AS Credito
            FROM Movimiento_Contable MC
            INNER JOIN Plan_Cuentas PC ON MC.Id_Plan_Cuenta = PC.Id_Plan_Cuentas
            WHERE DATE(MC.Fecha_Movimiento) BETWEEN '$fecha1' AND '$fecha2'
              AND MC.Estado != 'Anulado'
              $whereCentroCosto
            GROUP BY MC.Id_Plan_Cuenta
        ";
        $tipoResultado = 'Multiple';
    } else {
        $query = "
            SELECT 
                MC.Id_Plan_Cuenta,
                MC.Id_Centro_Costo,
                SUM($col_debe) AS Debito,
                SUM($col_haber) AS Credito
            FROM Movimiento_Contable MC
            INNER JOIN Plan_Cuentas PC ON PC.Id_Plan_Cuentas = MC.Id_Plan_Cuenta
            WHERE DATE(MC.Fecha_Movimiento) BETWEEN '$fecha1' AND '$fecha2'
              AND MC.Nit = $nit
              AND MC.Id_Plan_Cuenta = $plan
              AND MC.Estado != 'Anulado'
              $whereCentroCosto
        ";
        $tipoResultado = 'Unico';
    }

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo($tipoResultado);
    $movimientos = $oCon->getData();
    unset($oCon);

    return $movimientos;
}

/** ========================================================================================
 *  getMovimientosPorTipo() -> para ver detalle de documentos en caso $tipo == 'Tipo'
 * ======================================================================================== */
function getMovimientosPorTipo($fecha_ini, $fecha_fin, $id_plan_cuenta, $movimiento)
{
    global $centro_costo, $cond_exluir;
    if ($movimiento !== 'S') {
        return [];
    }
    $query = "
        SELECT
            MC.Id_Modulo,
            M.Documento AS Tipo_Documento,
            M.Prefijo,
            SUM(MC.Debe) AS Debe,
            SUM(MC.Haber) AS Haber,
            SUM(MC.Debe_Niif) AS Debe_Niif,
            SUM(MC.Haber_Niif) AS Haber_Niif
        FROM Movimiento_Contable MC
        INNER JOIN Plan_Cuentas PC ON PC.Id_Plan_Cuentas = MC.Id_Plan_Cuenta
        INNER JOIN Modulo M ON MC.Id_Modulo = M.Id_Modulo
        WHERE MC.Estado != 'Anulado'
          AND DATE(MC.Fecha_Movimiento) BETWEEN '$fecha_ini' AND '$fecha_fin'
          AND MC.Id_Plan_Cuenta = $id_plan_cuenta
          " . ($centro_costo !== false ? "AND MC.Id_Centro_Costo = {$centro_costo['Id_Centro_Costo']} AND PC.Codigo $cond_exluir" : "") . "
        GROUP BY MC.Id_Modulo
    ";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $resultado = $oCon->getData();
    unset($oCon);

    return $resultado;
}

/** ========================================================================================
 *  Función para obtener nits por cuentas contables
 * ======================================================================================== */
function nitsPorCuentasContables($id_plan_cuentas)
{
    global $fecha_ini, $fecha_fin, $centro_costo, $cond_exluir;

    $filtroCentroCosto = ($centro_costo !== false)
        ? "AND mc.Id_Centro_Costo = {$centro_costo['Id_Centro_Costo']} AND pc.Codigo $cond_exluir"
        : '';

    $query = "
    SELECT
        mc.Nit,
        COALESCE(
            c.Nombre,
            IF(
                p.Nombre IS NULL OR p.Nombre = '',
                CONCAT_WS(' ', p.Primer_Nombre, p.Segundo_Nombre, p.Primer_Apellido, p.Segundo_Apellido),
                p.Nombre
            ),
            CONCAT_WS(' ', f.Nombres, f.Apellidos),
            cc.Nombre
        ) AS Nombre,
        SUM(CASE WHEN mc.origen = 'BI' THEN mc.Debito_PCGA ELSE 0 END) AS Debito_PCGA,
        SUM(CASE WHEN mc.origen = 'BI' THEN mc.Credito_PCGA ELSE 0 END) AS Credito_PCGA,
        SUM(CASE WHEN mc.origen = 'BI' THEN mc.Debito_NIIF ELSE 0 END) AS Debito_NIIF,
        SUM(CASE WHEN mc.origen = 'BI' THEN mc.Credito_NIIF ELSE 0 END) AS Credito_NIIF,
        SUM(CASE WHEN mc.origen = 'MC' THEN mc.Total_Debito_Pcga ELSE 0 END) AS Total_Debito_Pcga,
        SUM(CASE WHEN mc.origen = 'MC' THEN mc.Total_Credito_Pcga ELSE 0 END) AS Total_Credito_Pcga,
        SUM(CASE WHEN mc.origen = 'MC' THEN mc.Total_Debito_Niif ELSE 0 END) AS Total_Debito_Niif,
        SUM(CASE WHEN mc.origen = 'MC' THEN mc.Total_Credito_Niif ELSE 0 END) AS Total_Credito_Niif
    FROM (
        SELECT
            bic.Nit,
            bic.Tipo AS Tipo_Nit,
            bic.Debito_PCGA,
            bic.Credito_PCGA,
            bic.Debito_NIIF,
            bic.Credito_NIIF,
            0 AS Total_Debito_Pcga,
            0 AS Total_Credito_Pcga,
            0 AS Total_Debito_Niif,
            0 AS Total_Credito_Niif,
            bic.Id_Centro_Costo,
            'BI' AS origen
        FROM Balance_Inicial_Contabilidad bic
        WHERE bic.Id_Plan_Cuentas = $id_plan_cuentas
          AND bic.Nit != 0
          " . ($centro_costo ? "AND bic.Id_Centro_Costo = {$centro_costo['Id_Centro_Costo']} AND bic.Codigo_Cuenta $cond_exluir" : "") . "

        UNION ALL

        SELECT
            m.Nit,
            m.Tipo_Nit,
            0,0,0,0,
            SUM(m.Debe), 
            SUM(m.Haber), 
            SUM(m.Debe_Niif), 
            SUM(m.Haber_Niif),
            m.Id_Centro_Costo,
            'MC' AS origen
        FROM Movimiento_Contable m
        INNER JOIN Plan_Cuentas pc ON pc.Id_Plan_Cuentas = m.Id_Plan_Cuenta
        WHERE m.Id_Plan_Cuenta = $id_plan_cuentas
          AND m.Nit != 0
          AND m.Estado != 'Anulado'
          AND DATE(m.Fecha_Movimiento) BETWEEN '$fecha_ini' AND '$fecha_fin'
          $filtroCentroCosto
        GROUP BY m.Nit, m.Id_Centro_Costo
    ) mc
    LEFT JOIN Cliente c ON c.Id_Cliente = mc.Nit AND mc.Tipo_Nit = 'Cliente'
    LEFT JOIN Proveedor p ON p.Id_Proveedor = mc.Nit AND mc.Tipo_Nit = 'Proveedor'
    LEFT JOIN Funcionario f ON f.Identificacion_Funcionario = mc.Nit AND mc.Tipo_Nit = 'Funcionario'
    LEFT JOIN Caja_Compensacion cc ON cc.Nit = mc.Nit AND mc.Tipo_Nit = 'Caja_Compensacion'
    GROUP BY mc.Nit
    ORDER BY mc.Nit
    ";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $resultado = $oCon->getData();
    unset($oCon);

    return $resultado;
}

/** ========================================================================================
 *  Armar totales de clases -> para la sumatoria final
 * ======================================================================================== */
function armarTotales($totales)
{
    // Solo si estás usando la lógica de totales en general
    $cuentas_clases = [];
    for ($i=1; $i <= 9; $i++) {
        $idx = (string)$i;
        $cuentas_clases[$idx] = [
            'saldo_anterior' => $totales['clases'][$idx]['saldo_anterior'] ?? 0,
            'nuevo_saldo'    => $totales['clases'][$idx]['nuevo_saldo']    ?? 0
        ];
    }
    return $cuentas_clases;
}

/** ========================================================================================
 *  getTotal() -> para la sumatoria final de SALDO
 * ======================================================================================== */
function getTotal($totales, $tipo)
{
    $cuentas_clases = armarTotales($totales);
    // Estructura simple basada en la fórmula dada:
    $A = $cuentas_clases['1'][$tipo] ?? 0;
    $B = $cuentas_clases['2'][$tipo] ?? 0;
    $C = $cuentas_clases['3'][$tipo] ?? 0;
    $D = $cuentas_clases['4'][$tipo] ?? 0;
    $E = $cuentas_clases['5'][$tipo] ?? 0;
    $F = $cuentas_clases['6'][$tipo] ?? 0;
    $G = $cuentas_clases['7'][$tipo] ?? 0;
    $H = $cuentas_clases['8'][$tipo] ?? 0;
    $I = $cuentas_clases['9'][$tipo] ?? 0;

    if ($tipo === 'saldo_anterior') {
        // (1 - 2 - 3) - (4 - 5 - 6 - 7 - 8 - 9)
        return ($A - $B - $C) - ($D - $E - $F - $G - $H - $I);
    } elseif ($tipo === 'nuevo_saldo') {
        // (1 - 2 - 3) - (4 - 5 - 6 - 7 - 8 - 9)
        return ($A - $B - $C) - ($D - $E - $F - $G - $H - $I);
    }
    return 0;
}

/** ========================================================================================
 *  AHORA ARMAMOS LA LÓGICA PRINCIPAL -> Generar PDF (switch)
 * ======================================================================================== */

// Obtenemos todos los movimientos (podemos reutilizar en cada caso)
$movimientos = getMovimientosCuenta($fecha_ini, $fecha_fin);

$totales = [
    "saldo_anterior" => 0,
    "debito"         => 0,
    "credito"        => 0,
    "nuevo_saldo"    => 0,
    "clases"         => []
];

// Preparar estilo + codigos de cabecera (arriba a la derecha)
$style = '<style>
.page-content{ width:750px; }
.row{ display:inline-block; width:750px; }
.td-header{ font-size:15px; line-height:20px; }
.titular{ font-size:11px; text-transform:uppercase; margin-bottom:0; }
</style>';

$tipo_balance = strtoupper($tipo);

$codigos = "
    <h4 style='margin:5px 0 0 0;font-size:19px;line-height:22px;'>BALANCE DE PRUEBA</h4>
    <h4 style='margin:5px 0 0 0;font-size:19px;line-height:22px;'>$tipo_balance</h4>
    <h4 style='margin:5px 0 0 0;font-size:19px;line-height:22px;'>".strtoupper($tipo_reporte)."</h4>
    <h5 style='margin:5px 0 0 0;font-size:16px;line-height:16px;'>Fecha Ini. ".fecha($fecha_ini)."</h5>
    <h5 style='margin:5px 0 0 0;font-size:16px;line-height:16px;'>Fecha Fin. ".fecha($fecha_fin)."</h5>
";

// Iniciamos contenido
$contenido = '<table style="font-size:10px;margin-top:10px;" cellpadding="0" cellspacing="0">
    <tr>
        <td style="width:50px;font-weight:bold;text-align:center;background:#cecece;border:1px solid #cccccc;">Cuenta</td>
        <td style="width:250px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">Nombre Cuenta</td>
        <td style="width:100px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">Saldo Anterior</td>
        <td style="width:100px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">Debitos</td>
        <td style="width:100px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">Creditos</td>
        <td style="width:100px;font-weight:bold;background:#cecece;text-align:center;border:1px solid #cccccc;">Nuevo Saldo</td>
    </tr>';

// Dependiendo de $tipo, ejecutamos la lógica
if ($tipo === 'General') {
    // Consulta optimizada para 'General'
    $centroCond = ($centro_costo !== false)
        ? " AND BI.Id_Centro_Costo = {$centro_costo['Id_Centro_Costo']} AND BI.Codigo_Cuenta $cond_exluir "
        : "";

    $queryGeneral = "
        SELECT 
            PC.Codigo,
            PC.Nombre,
            PC.Codigo_Niif,
            PC.Nombre_Niif,
            PC.Naturaleza,
            COALESCE(SUM(BI.Debito_PCGA), 0) AS Debito_PCGA,
            COALESCE(SUM(BI.Credito_PCGA), 0) AS Credito_PCGA,
            COALESCE(SUM(BI.Debito_NIIF), 0) AS Debito_NIIF,
            COALESCE(SUM(BI.Credito_NIIF), 0) AS Credito_NIIF,
            PC.Estado,
            PC.Movimiento,
            PC.Tipo_P
        FROM Plan_Cuentas PC
        LEFT JOIN Balance_Inicial_Contabilidad BI 
            ON PC.Id_Plan_Cuentas = BI.Id_Plan_Cuentas
            AND BI.Fecha = '$ultimo_dia_mes'
            $centroCond
        ".getStrCondiciones()."
        GROUP BY PC.Id_Plan_Cuentas
        HAVING PC.Estado = 'ACTIVO' 
           OR (PC.Estado = 'INACTIVO' AND (Debito_PCGA > 0 OR Credito_PCGA > 0 OR Debito_NIIF > 0 OR Credito_NIIF > 0))
        ORDER BY " . ($tipo_reporte === 'Pcga' ? 'PC.Codigo' : 'PC.Codigo_Niif');

    $oCon = new consulta();
    $oCon->setQuery($queryGeneral);
    $oCon->setTipo('Multiple');
    $balance = $oCon->getData();
    unset($oCon);

    foreach ($balance as $i => $value) {
        $codigo = ($tipo_reporte === 'Pcga') ? $value['Codigo'] : $value['Codigo_Niif'];
        $nombre_cta = ($tipo_reporte === 'Pcga') ? $value['Nombre'] : $value['Nombre_Niif'];

        $saldo_anterior = obtenerSaldoAnterior($value['Naturaleza'], $balance, $i, $tipo_reporte);
        $debito         = calcularMovimiento($codigo, $movimientos, 'Debito');
        $credito        = calcularMovimiento($codigo, $movimientos, 'Credito');
        $nuevo_saldo    = calcularNuevoSaldo($value['Naturaleza'], $saldo_anterior, $debito, $credito);

        if ($saldo_anterior != 0 || $debito != 0 || $credito != 0 || $nuevo_saldo != 0) {
            $contenido .= "
            <tr>
                <td style='padding:4px;text-align:left;border:1px solid #cccccc;'>$codigo</td>
                <td style='width:250px;padding:4px;text-align:left;border:1px solid #cccccc;'>$nombre_cta</td>
                <td style='padding:4px;text-align:right;border:1px solid #cccccc;'>$ " . number_format($saldo_anterior, 2, ',', '.') . "</td>
                <td style='padding:4px;text-align:right;border:1px solid #cccccc;'>$ " . number_format($debito, 2, ',', '.') . "</td>
                <td style='padding:4px;text-align:right;border:1px solid #cccccc;'>$ " . number_format($credito, 2, ',', '.') . "</td>
                <td style='padding:4px;text-align:right;border:1px solid #cccccc;'>$ " . number_format($nuevo_saldo, 2, ',', '.') . "</td>
            </tr>";
            if ($value['Tipo_P'] === 'CLASE') {
                $totales['clases'][$value['Codigo']]['saldo_anterior'] = $saldo_anterior;
                $totales['clases'][$value['Codigo']]['nuevo_saldo']    = $nuevo_saldo;
                $totales['debito']  += $debito;
                $totales['credito'] += $credito;
            }
        }
    }

    $totales['saldo_anterior'] = getTotal($totales, 'saldo_anterior');
    $totales['nuevo_saldo']    = getTotal($totales, 'nuevo_saldo');

    // Ajuste temporal
    if ($totales['nuevo_saldo'] != 0) {
        $totales['credito'] += $totales['nuevo_saldo'];
        $totales['nuevo_saldo'] = 0;
    }

    // Fila total
    $contenido .= "
    <tr>
        <td colspan='2' style='background:#cecece;padding:4px;text-align:left;border:1px solid #cccccc;'>Total</td>
        <td style='background:#cecece;padding:4px;text-align:right;border:1px solid #cccccc;'>$ " . number_format($totales['saldo_anterior'], 2, ',', '.') . "</td>
        <td style='background:#cecece;padding:4px;text-align:right;border:1px solid #cccccc;'>$ " . number_format($totales['debito'], 2, ',', '.') . "</td>
        <td style='background:#cecece;padding:4px;text-align:right;border:1px solid #cccccc;'>$ " . number_format($totales['credito'], 2, ',', '.') . "</td>
        <td style='background:#cecece;padding:4px;text-align:right;border:1px solid #cccccc;'>$ " . number_format($totales['nuevo_saldo'], 2, ',', '.') . "</td>
    </tr>";

} elseif ($tipo === 'Nits') {
    // Query nits (subconsulta original). Se dejó parte, pero optimizado

    $centroCond = ($centro_costo !== false)
        ? " AND BI.Id_Centro_Costo = {$centro_costo['Id_Centro_Costo']} AND BI.Codigo_Cuenta $cond_exluir "
        : "";

    $queryNits = "
        SELECT 
            PC.Id_Plan_Cuentas,
            PC.Codigo,
            PC.Nombre,
            PC.Codigo_Niif,
            PC.Nombre_Niif,
            PC.Naturaleza,
            IFNULL(SUM(BIC.Debito_PCGA), 0)   AS Debito_PCGA,
            IFNULL(SUM(BIC.Credito_PCGA), 0)  AS Credito_PCGA,
            IFNULL(SUM(BIC.Debito_NIIF), 0)   AS Debito_NIIF,
            IFNULL(SUM(BIC.Credito_NIIF), 0)  AS Credito_NIIF,
            PC.Estado,
            PC.Movimiento,
            PC.Tipo_P
        FROM Plan_Cuentas PC
        LEFT JOIN (
            SELECT * 
            FROM Balance_Inicial_Contabilidad 
            WHERE Fecha = '$ultimo_dia_mes'
            " . ($centro_costo !== false ? " AND Id_Centro_Costo = {$centro_costo['Id_Centro_Costo']}  AND Codigo_Cuenta $cond_exluir " : "") . "
        ) BIC ON BIC.Id_Plan_Cuentas = PC.Id_Plan_Cuentas
        " . getStrCondiciones() . "
        GROUP BY PC.Id_Plan_Cuentas
        HAVING PC.Estado = 'ACTIVO' 
           OR (PC.Estado = 'INACTIVO' AND (Debito_PCGA > 0 OR Credito_PCGA > 0 OR Debito_NIIF > 0 OR Credito_NIIF > 0))
        ORDER BY " . ($tipo_reporte === 'Pcga' ? 'PC.Codigo' : 'PC.Codigo_Niif');

    $oCon = new consulta();
    $oCon->setQuery($queryNits);
    $oCon->setTipo('Multiple');
    $balance = $oCon->getData();
    unset($oCon);

    // Por cada cuenta, cargar nits
    foreach ($balance as $i => $val) {
        $balance[$i]['nits'] = nitsPorCuentasContables($val['Id_Plan_Cuentas']);
    }

    // Recorremos
    foreach ($balance as $i => $value) {
        $codigo     = ($tipo_reporte === 'Pcga') ? $value['Codigo'] : $value['Codigo_Niif'];
        $nombre_cta = ($tipo_reporte === 'Pcga') ? $value['Nombre'] : $value['Nombre_Niif'];

        $saldo_anterior = obtenerSaldoAnterior($value['Naturaleza'], $balance, $i, $tipo_reporte);
        
        $debito         = calcularMovimiento($codigo, $movimientos, 'Debito');
        $credito        = calcularMovimiento($codigo, $movimientos, 'Credito');
        $nuevo_saldo    = calcularNuevoSaldo($value['Naturaleza'], $saldo_anterior, $debito, $credito);
        
        if ($saldo_anterior != 0 || $debito != 0 || $credito != 0 || $nuevo_saldo != 0) {
            $contenido .= "
            <tr>
                <td style='padding:4px;text-align:left;border:1px solid #cccccc;'>$codigo</td>
                <td style='width:250px;padding:4px;text-align:left;border:1px solid #cccccc;'>$nombre_cta</td>
                <td style='padding:4px;text-align:right;border:1px solid #cccccc;'>$ " . number_format($saldo_anterior, 2, ',', '.') . "</td>
                <td style='padding:4px;text-align:right;border:1px solid #cccccc;'>$ " . number_format($debito, 2, ',', '.') . "</td>
                <td style='padding:4px;text-align:right;border:1px solid #cccccc;'>$ " . number_format($credito, 2, ',', '.') . "</td>
                <td style='padding:4px;text-align:right;border:1px solid #cccccc;'>$ " . number_format($nuevo_saldo, 2, ',', '.') . "</td>
            </tr>";
            if ($value['Tipo_P'] === 'CLASE') {
                $totales['clases'][$value['Codigo']]['saldo_anterior'] = $saldo_anterior;
                $totales['clases'][$value['Codigo']]['nuevo_saldo']    = $nuevo_saldo;
                $totales['debito']  += $debito;
                $totales['credito'] += $credito;
            }
        }
        
      
        // Renderizar nits
        $nits = $value['nits'];
        foreach ($nits as $j => $nit) {
            $saldo_ant_nit = obtenerSaldoAnterior($value['Naturaleza'], $nits, $j, $tipo_reporte, $nit['Nit'], $value['Id_Plan_Cuentas']);
            $debN  = $nit['Total_Debito_'.strtoupper($tipo_reporte)];
            $credN = $nit['Total_Credito_'.strtoupper($tipo_reporte)];
            $nuevoN = calcularNuevoSaldo($value['Naturaleza'], $saldo_ant_nit, $debN, $credN);

            if ($saldo_ant_nit != 0 || $debN != 0 || $credN != 0 || $nuevoN != 0) {
                $contenido .= "
                <tr>
                    <td style='font-size:9px;color:gray;padding:2px;text-align:left;border:1px solid #cccccc;'>{$nit['Nit']}</td>
                    <td style='width:250px;font-size:9px;color:gray;padding:2px;text-align:left;border:1px solid #cccccc;'>{$nit['Nombre']}</td>
                    <td style='font-size:9px;color:gray;padding:2px;text-align:right;border:1px solid #cccccc;'>$ ".number_format($saldo_ant_nit,2,',','.')."</td>
                    <td style='font-size:9px;color:gray;padding:2px;text-align:right;border:1px solid #cccccc;'>$ ".number_format($debN,2,',','.')."</td>
                    <td style='font-size:9px;color:gray;padding:2px;text-align:right;border:1px solid #cccccc;'>$ ".number_format($credN,2,',','.')."</td>
                    <td style='font-size:9px;color:gray;padding:2px;text-align:right;border:1px solid #cccccc;'>$ ".number_format($nuevoN,2,',','.')."</td>
                </tr>";
            }
        }
    }

    $totales['saldo_anterior'] = getTotal($totales, 'saldo_anterior');
    $totales['nuevo_saldo']    = getTotal($totales, 'nuevo_saldo');

    // Ajuste temporal
    if ($totales['nuevo_saldo'] != 0) {
        $totales['credito'] += $totales['nuevo_saldo'];
        $totales['nuevo_saldo'] = 0;
    }

    $contenido .= "
    <tr>
        <td colspan='2' style='background:#cecece;padding:4px;text-align:left;border:1px solid #cccccc;'>Total</td>
        <td style='background:#cecece;padding:4px;text-align:right;border:1px solid #cccccc;'>$ ".number_format($totales['saldo_anterior'], 2, ',', '.')."</td>
        <td style='background:#cecece;padding:4px;text-align:right;border:1px solid #cccccc;'>$ ".number_format($totales['debito'], 2, ',', '.')."</td>
        <td style='background:#cecece;padding:4px;text-align:right;border:1px solid #cccccc;'>$ ".number_format($totales['credito'], 2, ',', '.')."</td>
        <td style='background:#cecece;padding:4px;text-align:right;border:1px solid #cccccc;'>$ ".number_format($totales['nuevo_saldo'], 2, ',', '.')."</td>
    </tr>";

} elseif ($tipo === 'Tipo') {
    // Query para Tipo
    $queryTipo = "
        SELECT 
            PC.Id_Plan_Cuentas,
            PC.Codigo,
            PC.Nombre,
            PC.Codigo_Niif,
            PC.Nombre_Niif,
            PC.Naturaleza,
            IFNULL(SUM(BIC.Debito_PCGA), 0) AS Debito_PCGA,
            IFNULL(SUM(BIC.Credito_PCGA), 0) AS Credito_PCGA,
            IFNULL(SUM(BIC.Debito_NIIF), 0) AS Debito_NIIF,
            IFNULL(SUM(BIC.Credito_NIIF), 0) AS Credito_NIIF,
            PC.Estado,
            PC.Movimiento,
            PC.Tipo_P
        FROM Plan_Cuentas PC
        LEFT JOIN (
            SELECT * 
            FROM Balance_Inicial_Contabilidad 
            WHERE Fecha = '$ultimo_dia_mes'
        ) BIC ON BIC.Id_Plan_Cuentas = PC.Id_Plan_Cuentas
        ".getStrCondiciones()."
        GROUP BY PC.Id_Plan_Cuentas
        HAVING PC.Estado = 'ACTIVO' 
           OR (PC.Estado = 'INACTIVO' AND (Debito_PCGA > 0 OR Credito_PCGA > 0 OR Debito_NIIF > 0 OR Credito_NIIF > 0))
        ORDER BY ".($tipo_reporte === 'Pcga' ? 'PC.Codigo' : 'PC.Codigo_Niif');

    $oCon = new consulta();
    $oCon->setQuery($queryTipo);
    $oCon->setTipo('Multiple');
    $balance = $oCon->getData();
    unset($oCon);

    // Para cada cuenta, traemos movimientos por Tipo
    foreach ($balance as $i => $val) {
        $balance[$i]['tipos'] = getMovimientosPorTipo($fecha_ini, $fecha_fin, $val['Id_Plan_Cuentas'], $val['Movimiento']);
    }

    foreach ($balance as $i => $value) {
        $codigo     = ($tipo_reporte === 'Pcga') ? $value['Codigo'] : $value['Codigo_Niif'];
        $nombre_cta = ($tipo_reporte === 'Pcga') ? $value['Nombre'] : $value['Nombre_Niif'];

        $saldo_anterior = obtenerSaldoAnterior($value['Naturaleza'], $balance, $i, $tipo_reporte);
        $debito         = calcularMovimiento($codigo, $movimientos, 'Debito');
        $credito        = calcularMovimiento($codigo, $movimientos, 'Credito');
        $nuevo_saldo    = calcularNuevoSaldo($value['Naturaleza'], $saldo_anterior, $debito, $credito);

        if ($saldo_anterior != 0 || $debito != 0 || $credito != 0 || $nuevo_saldo != 0) {
            $contenido .= "
            <tr>
                <td style='padding:4px;text-align:left;border:1px solid #cccccc;'>$codigo</td>
                <td style='width:250px;padding:4px;text-align:left;border:1px solid #cccccc;'>$nombre_cta</td>
                <td style='padding:4px;text-align:right;border:1px solid #cccccc;'>$ ".number_format($saldo_anterior, 2, ',', '.')."</td>
                <td style='padding:4px;text-align:right;border:1px solid #cccccc;'>$ ".number_format($debito, 2, ',', '.')."</td>
                <td style='padding:4px;text-align:right;border:1px solid #cccccc;'>$ ".number_format($credito, 2, ',', '.')."</td>
                <td style='padding:4px;text-align:right;border:1px solid #cccccc;'>$ ".number_format($nuevo_saldo, 2, ',', '.')."</td>
            </tr>";

            if ($value['Tipo_P'] === 'CLASE') {
                $totales['clases'][$value['Codigo']]['saldo_anterior'] = $saldo_anterior;
                $totales['clases'][$value['Codigo']]['nuevo_saldo']    = $nuevo_saldo;
                $totales['debito']  += $debito;
                $totales['credito'] += $credito;
            }
        }

        $tiposMov = $value['tipos'];
        if (!empty($tiposMov)) {
            $debe_col  = ($tipo_reporte === 'Pcga') ? 'Debe'      : 'Debe_Niif';
            $haber_col = ($tipo_reporte === 'Pcga') ? 'Haber'     : 'Haber_Niif';

            $contenido .= "
            <tr>
                <td style='padding:4px;text-align:left;border:1px solid #cccccc;'></td>
                <td colspan='5' style='padding:4px;text-align:left;border:1px solid #cccccc;'>
                    <table>";

            foreach ($tiposMov as $movT) {
                $contenido .= "
                        <tr>
                            <td style='width:50px;font-size:9px;color:gray;padding:2px;text-align:left;'>{$movT['Prefijo']}</td>
                            <td style='width:160px;font-size:9px;color:gray;padding:2px;text-align:left;'>{$movT['Tipo_Documento']}</td>
                            <td style='width:100px;font-size:9px;color:gray;padding:2px;text-align:right;'>0,00</td>
                            <td style='width:100px;font-size:9px;color:gray;padding:2px;text-align:right;'>$ ".number_format($movT[$debe_col], 2, ',', '.')."</td>
                            <td style='width:100px;font-size:9px;color:gray;padding:2px;text-align:right;'>$ ".number_format($movT[$haber_col], 2, ',', '.')."</td>
                            <td style='width:90px;font-size:9px;color:gray;padding:2px;text-align:right;'>0,00</td>
                        </tr>";
            }
            $contenido .= "
                    </table>
                </td>
            </tr>";
        }
    }

    $totales['saldo_anterior'] = getTotal($totales, 'saldo_anterior');
    $totales['nuevo_saldo']    = getTotal($totales, 'nuevo_saldo');

    // Ajuste temporal
    if ($totales['nuevo_saldo'] != 0) {
        $totales['credito'] += $totales['nuevo_saldo'];
        $totales['nuevo_saldo'] = 0;
    }

    $contenido .= "
    <tr>
        <td colspan='2' style='background:#cecece;padding:4px;text-align:left;border:1px solid #cccccc;'>Total</td>
        <td style='background:#cecece;padding:4px;text-align:right;border:1px solid #cccccc;'>$ ".number_format($totales['saldo_anterior'], 2, ',', '.')."</td>
        <td style='background:#cecece;padding:4px;text-align:right;border:1px solid #cccccc;'>$ ".number_format($totales['debito'], 2, ',', '.')."</td>
        <td style='background:#cecece;padding:4px;text-align:right;border:1px solid #cccccc;'>$ ".number_format($totales['credito'], 2, ',', '.')."</td>
        <td style='background:#cecece;padding:4px;text-align:right;border:1px solid #cccccc;'>$ ".number_format($totales['nuevo_saldo'], 2, ',', '.')."</td>
    </tr>";
}

$contenido .= '</table>';

/** ========================================================================================
 *  Armado de cabecera PDF + Render final
 * ======================================================================================== */
$cabecera = "
<table>
  <tbody>
    <tr>
      <td style='width:70px;'>
        <img src='".$_SERVER["DOCUMENT_ROOT"]."assets/images/LogoProh.jpg' style='width:60px;' alt='Pro-H Software'/>
      </td>
      <td class='td-header' style='width:410px;font-weight:thin;font-size:14px;line-height:20px;'>
        {$config['Nombre_Empresa']}<br>
        N.I.T.: {$config['NIT']}<br>
        {$config['Direccion']}<br>
        TEL: {$config['Telefono']}
      </td>
      <td style='width:250px;text-align:right'>
        $codigos
      </td>
    </tr>
  </tbody>
</table>
<hr style='border:1px dotted #ccc;width:730px;'>";

$content = "
<page backtop='0mm' backbottom='0mm'>
    <div class='page-content'>
        $style
        $cabecera
        $contenido
    </div>
</page>";

//echo $content; exit;

try {
    $html2pdf = new HTML2PDF('P', 'A4', 'Es', true, 'UTF-8', array(5,5,5,5));
    $html2pdf->writeHTML($content);
    $direc = 'Balance_Prueba'.$fecha_ini.' al '.$fecha_fin.'.pdf';
    $html2pdf->Output($direc);
} catch (HTML2PDF_exception $e) {
    echo $e;
    exit;
}
