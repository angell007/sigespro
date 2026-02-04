<?php

ini_set('memory_limit', '2048M');
set_time_limit(0);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: text/plain; charset=utf-8');

require_once('../../../config/start.inc.php');

date_default_timezone_set("America/Bogota");

function getParam($key, $default = '') {
    if (isset($_GET[$key])) {
        return $_GET[$key];
    }
    if (isset($_POST[$key])) {
        return $_POST[$key];
    }
    return isset($_REQUEST[$key]) ? $_REQUEST[$key] : $default;
}

$fecha_inicio = getParam('Fecha_Inicial', '2026-01-01');
$fecha_fin = getParam('Fecha_Final', '2026-01-31');
$ejecutar = getParam('Ejecutar', '0') === '1';

echo "Proceso actualizar costos facturas\n";
echo "Rango: $fecha_inicio a $fecha_fin\n";
echo "Ejecutar: " . ($ejecutar ? 'SI' : 'NO (dry-run)') . "\n\n";

global $db_host, $db_user, $db_password, $db_name;
$link = mysqli_connect($db_host, $db_user, $db_password, $db_name);
if (!$link) {
    echo "Error conexion: " . mysqli_connect_error() . "\n";
    exit;
}
mysqli_set_charset($link, "utf8");

// 1) Actualizar Producto_Dispensacion.Costo con Costo_Promedio (unitario)
$sql_update_costos = "
    UPDATE Producto_Dispensacion PD
    INNER JOIN Producto_Factura PF ON PF.Id_Producto_Dispensacion = PD.Id_Producto_Dispensacion
    INNER JOIN Factura F ON F.Id_Factura = PF.Id_Factura
    LEFT JOIN Costo_Promedio CP ON CP.Id_Producto = PF.Id_Producto
    SET PD.Costo = IFNULL(CP.Costo_Promedio, 0)
    WHERE F.Estado_Factura NOT IN ('Anulada')
      AND DATE(F.Fecha_Documento) BETWEEN '$fecha_inicio' AND '$fecha_fin'
";

echo "SQL update costos:\n$sql_update_costos\n\n";

if ($ejecutar) {
    if (!mysqli_query($link, $sql_update_costos)) {
        echo "Error update costos: " . mysqli_error($link) . "\n";
        mysqli_close($link);
        exit;
    }
    $afectados = mysqli_affected_rows($link);
    echo "Producto_Dispensacion actualizados: $afectados\n\n";
} else {
    echo "Dry-run: no se actualiza Producto_Dispensacion.\n\n";
}

// 2) Obtener facturas del rango
$sql_facturas = "
    SELECT F.Id_Factura, F.Codigo, F.Fecha_Documento
    FROM Factura F
    WHERE F.Estado_Factura NOT IN ('Anulada')
      AND DATE(F.Fecha_Documento) BETWEEN '$fecha_inicio' AND '$fecha_fin'
";

$res_fact = mysqli_query($link, $sql_facturas);
if (!$res_fact) {
    echo "Error facturas: " . mysqli_error($link) . "\n";
    mysqli_close($link);
    exit;
}

$facturas = [];
while ($row = mysqli_fetch_assoc($res_fact)) {
    $facturas[] = $row;
}
mysqli_free_result($res_fact);

echo "Facturas encontradas: " . count($facturas) . "\n\n";

// 3) Recalcular movimientos de costo (cuentas 6/7) por factura
$impuestos_posibles = [0, 5, 19];
$total_actualizadas = 0;
$total_sin_mov = 0;
$total_sin_asociacion = 0;

foreach ($facturas as $factura) {
    $id_factura = (int)$factura['Id_Factura'];
    $codigo = $factura['Codigo'];

    // Movimiento base para datos de encabezado
    $sql_mov_base = "
        SELECT Id_Modulo, Nit, Tipo_Nit, Documento, Numero_Comprobante, Id_Centro_Costo, Fecha_Movimiento
        FROM Movimiento_Contable
        WHERE Id_Registro_Modulo = $id_factura AND Estado != 'Anulado'
        ORDER BY Id_Movimiento_Contable ASC
        LIMIT 1
    ";
    $res_mov = mysqli_query($link, $sql_mov_base);
    if (!$res_mov) {
        echo "Error mov base ($codigo): " . mysqli_error($link) . "\n";
        continue;
    }
    $mov_base = mysqli_fetch_assoc($res_mov);
    mysqli_free_result($res_mov);

    if (!$mov_base) {
        $total_sin_mov++;
        continue;
    }

    $id_modulo = (int)$mov_base['Id_Modulo'];
    $nit = $mov_base['Nit'];
    $tipo_nit = $mov_base['Tipo_Nit'];
    $documento = $mov_base['Documento'];
    $numero_comprobante = $mov_base['Numero_Comprobante'];
    $id_centro_costo = $mov_base['Id_Centro_Costo'];
    $fecha_mov = $mov_base['Fecha_Movimiento'];

    // Costos por impuesto
    $sql_costos = "
        SELECT PF.Impuesto, SUM(IFNULL(CP.Costo_Promedio, 0) * PF.Cantidad) AS Costo
        FROM Producto_Factura PF
        LEFT JOIN Costo_Promedio CP ON CP.Id_Producto = PF.Id_Producto
        WHERE PF.Id_Factura = $id_factura
        GROUP BY PF.Impuesto
    ";
    $res_costos = mysqli_query($link, $sql_costos);
    if (!$res_costos) {
        echo "Error costos ($codigo): " . mysqli_error($link) . "\n";
        continue;
    }
    $costos_impuesto = [];
    while ($row = mysqli_fetch_assoc($res_costos)) {
        $key = $row['Impuesto'] === null ? '0' : (string)intval($row['Impuesto']);
        $costos_impuesto[$key] = (float)$row['Costo'];
    }
    mysqli_free_result($res_costos);

    // Obtener cuentas de costo por impuesto (para borrar e insertar)
    $cuentas_costo = [];
    $cuentas_contra = [];
    foreach ($impuestos_posibles as $imp) {
        $sql_costo = "
            SELECT Id_Plan_Cuenta
            FROM Asociacion_Plan_Cuentas
            WHERE Busqueda_Interna = 'costo gravado $imp'
              AND Id_Modulo = $id_modulo
            LIMIT 1
        ";
        $res_costo = mysqli_query($link, $sql_costo);
        $row_costo = $res_costo ? mysqli_fetch_assoc($res_costo) : null;
        if ($res_costo) { mysqli_free_result($res_costo); }
        if ($row_costo && isset($row_costo['Id_Plan_Cuenta'])) {
            $cuentas_costo[$imp] = (int)$row_costo['Id_Plan_Cuenta'];
        }

        $sql_contra = "
            SELECT Id_Plan_Cuenta
            FROM Asociacion_Plan_Cuentas
            WHERE Busqueda_Interna = 'costo contraparte gravado $imp'
              AND Id_Modulo = $id_modulo
            LIMIT 1
        ";
        $res_contra = mysqli_query($link, $sql_contra);
        $row_contra = $res_contra ? mysqli_fetch_assoc($res_contra) : null;
        if ($res_contra) { mysqli_free_result($res_contra); }
        if ($row_contra && isset($row_contra['Id_Plan_Cuenta'])) {
            $cuentas_contra[$imp] = (int)$row_contra['Id_Plan_Cuenta'];
        }
    }

    $ids_plan_cuenta = array_values(array_unique(array_merge(array_values($cuentas_costo), array_values($cuentas_contra))));
    if (empty($ids_plan_cuenta)) {
        $total_sin_asociacion++;
        continue;
    }

    // Insertar nuevos costos por impuesto
    foreach ($costos_impuesto as $imp => $costo_total) {
        if ($costo_total <= 0) {
            continue;
        }
        $imp_int = (int)$imp;
        if (!isset($cuentas_costo[$imp_int]) || !isset($cuentas_contra[$imp_int])) {
            continue;
        }
        $costo_total = number_format($costo_total, 2, ".", "");

        $sql_update_debe = "
            UPDATE Movimiento_Contable
            SET Debe = '$costo_total',
                Debe_Niif = '$costo_total',
                Haber = '0',
                Haber_Niif = '0',
                Fecha_Movimiento = '$fecha_mov',
                Nit = '$nit',
                Tipo_Nit = '$tipo_nit',
                Documento = '$documento',
                Numero_Comprobante = '$numero_comprobante'
            WHERE Id_Modulo = $id_modulo
              AND Id_Registro_Modulo = $id_factura
              AND Id_Plan_Cuenta = {$cuentas_costo[$imp_int]}
              AND Estado != 'Anulado'
        ";
        $sql_update_haber = "
            UPDATE Movimiento_Contable
            SET Debe = '0',
                Debe_Niif = '0',
                Haber = '$costo_total',
                Haber_Niif = '$costo_total',
                Fecha_Movimiento = '$fecha_mov',
                Nit = '$nit',
                Tipo_Nit = '$tipo_nit',
                Documento = '$documento',
                Numero_Comprobante = '$numero_comprobante'
            WHERE Id_Modulo = $id_modulo
              AND Id_Registro_Modulo = $id_factura
              AND Id_Plan_Cuenta = {$cuentas_contra[$imp_int]}
              AND Estado != 'Anulado'
        ";

        $sql_insert_debe = "
            INSERT INTO Movimiento_Contable
            (Id_Plan_Cuenta, Id_Modulo, Id_Registro_Modulo, Fecha_Movimiento, Debe, Debe_Niif, Haber, Haber_Niif, Nit, Tipo_Nit, Documento, Id_Centro_Costo, Numero_Comprobante, Detalles, Estado)
            VALUES
            ({$cuentas_costo[$imp_int]}, $id_modulo, $id_factura, '$fecha_mov', '$costo_total', '$costo_total', '0', '0', '$nit', '$tipo_nit', '$documento', " . ($id_centro_costo === null ? "NULL" : "'$id_centro_costo'") . ", '$numero_comprobante', 'Costo Factura', 'Activo')
        ";
        $sql_insert_haber = "
            INSERT INTO Movimiento_Contable
            (Id_Plan_Cuenta, Id_Modulo, Id_Registro_Modulo, Fecha_Movimiento, Debe, Debe_Niif, Haber, Haber_Niif, Nit, Tipo_Nit, Documento, Id_Centro_Costo, Numero_Comprobante, Detalles, Estado)
            VALUES
            ({$cuentas_contra[$imp_int]}, $id_modulo, $id_factura, '$fecha_mov', '0', '0', '$costo_total', '$costo_total', '$nit', '$tipo_nit', '$documento', " . ($id_centro_costo === null ? "NULL" : "'$id_centro_costo'") . ", '$numero_comprobante', 'Costo Factura', 'Activo')
        ";

        if ($ejecutar) {
            if (!mysqli_query($link, $sql_update_debe)) {
                echo "Error update debe ($codigo, imp $imp): " . mysqli_error($link) . "\n";
                continue;
            }
            if (!mysqli_query($link, $sql_update_haber)) {
                echo "Error update haber ($codigo, imp $imp): " . mysqli_error($link) . "\n";
                continue;
            }
            if (mysqli_affected_rows($link) === 0) {
                if (!mysqli_query($link, $sql_insert_debe)) {
                    echo "Error insert debe ($codigo, imp $imp): " . mysqli_error($link) . "\n";
                    continue;
                }
            }
            if (mysqli_affected_rows($link) === 0) {
                if (!mysqli_query($link, $sql_insert_haber)) {
                    echo "Error insert haber ($codigo, imp $imp): " . mysqli_error($link) . "\n";
                    continue;
                }
            }
        }
    }

    $total_actualizadas++;
}

echo "\nResumen:\n";
echo "Facturas procesadas: " . count($facturas) . "\n";
echo "Facturas sin movimientos: $total_sin_mov\n";
echo "Facturas sin asociacion de costo: $total_sin_asociacion\n";
echo "Facturas con recosteo aplicado: $total_actualizadas\n";

mysqli_close($link);

?>
