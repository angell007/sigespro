<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-type:application/json');

set_time_limit(0);
ignore_user_abort(true);

require_once '../../config/start.inc.php';
require_once '../../class/class.consulta.php';
require_once '../../class/class.qr.php';
require_once '../../class/class.facturacion_electronica.php';
define('REPROCESO_CUFE_DIARIO', true);

if (empty($_SERVER['DOCUMENT_ROOT'])) {
    $_SERVER['DOCUMENT_ROOT'] = APP_ROOT;
}

$fecha_inicio = isset($_REQUEST['fecha_inicio']) ? $_REQUEST['fecha_inicio'] : '';
$fecha_fin = isset($_REQUEST['fecha_fin']) ? $_REQUEST['fecha_fin'] : '';
$tabla = isset($_REQUEST['tabla']) ? $_REQUEST['tabla'] : 'Factura';
$reprocesar_dian = isset($_REQUEST['reprocesar_dian']) ? filter_var($_REQUEST['reprocesar_dian'], FILTER_VALIDATE_BOOLEAN) : false;
$solo_sin_cufe = isset($_REQUEST['solo_sin_cufe']) ? filter_var($_REQUEST['solo_sin_cufe'], FILTER_VALIDATE_BOOLEAN) : true;
$por_factura = isset($_REQUEST['por_factura']) ? filter_var($_REQUEST['por_factura'], FILTER_VALIDATE_BOOLEAN) : false;
$lote = isset($_REQUEST['lote']) ? (int)$_REQUEST['lote'] : ($por_factura ? 1 : 200);
$lote = max(1, $lote);
$log_file = APP_ROOT . '/tmp/recuperar_cufe_dian.log';
asegurarLogPath($log_file);

$tablas_soportadas = [
    'Factura' => ['pk' => 'Id_Factura', 'fecha' => 'Fecha_Documento'],
    'Factura_Venta' => ['pk' => 'Id_Factura_Venta', 'fecha' => 'Fecha_Documento'],
    'Factura_Administrativa' => ['pk' => 'Id_Factura_Administrativa', 'fecha' => 'Fecha'],
    'Factura_Capita' => ['pk' => 'Id_Factura_Capita', 'fecha' => 'Fecha_Documento'],
];

if (!validarFecha($fecha_inicio) || !validarFecha($fecha_fin)) {
    http_response_code(400);
    echo json_encode(['error' => 'Debe enviar fecha_inicio y fecha_fin con formato YYYY-MM-DD.']);
    exit;
}

if (!isset($tablas_soportadas[$tabla])) {
    http_response_code(400);
    echo json_encode(['error' => 'Tabla no soportada.', 'tablas' => array_keys($tablas_soportadas)]);
    exit;
}

$configuracion = obtenerConfiguracion();
$nit = normalizarNit($configuracion['NIT']);
$resumen = [
    'procesadas' => 0,
    'actualizadas' => 0,
    'sin_cufe' => 0,
    'errores' => [],
    'registros' => [],
];

$tabla_pk = $tablas_soportadas[$tabla]['pk'];
$campo_fecha = $tablas_soportadas[$tabla]['fecha'];

$ultimo_id = 0;
do {
    $facturas = obtenerFacturasSinCufe($tabla, $tabla_pk, $campo_fecha, $fecha_inicio, $fecha_fin, $solo_sin_cufe, $ultimo_id, $lote);
    foreach ($facturas as $factura) {
    try {
        $resumen['procesadas']++;
        escribirLog('inicio_factura', ['id' => $factura['Id'], 'codigo' => $factura['Codigo'], 'tabla' => $tabla]);

        $resolucion = obtenerResolucion($factura['Id_Resolucion']);
        if (!$resolucion) {
            $resumen['errores'][] = [
                'id' => $factura['Id'],
                'codigo' => $factura['Codigo'],
                'error' => 'Resolucion no encontrada',
            ];
            escribirLog('sin_resolucion', ['id' => $factura['Id'], 'codigo' => $factura['Codigo'], 'id_resolucion' => $factura['Id_Resolucion']]);
            continue;
        }

        $nombre_archivo = construirNombreFactura($factura['Codigo'], $resolucion['Codigo'], $nit, $factura['Fecha']);
        escribirLog('nombre_archivo', ['id' => $factura['Id'], 'codigo' => $factura['Codigo'], 'resolution_id' => $resolucion['resolution_id'], 'nombre_archivo' => $nombre_archivo]);
        $cufe_dian = obtenerCufeDesdeDian($resolucion['resolution_id'], $nombre_archivo);

        if (!$cufe_dian['cufe'] && $reprocesar_dian && !empty($cufe_dian['missing_xml'])) {
            $cufe_dian = reprocesarFacturaDian($tabla, $factura, $resolucion);
        } elseif (!$cufe_dian['cufe'] && $reprocesar_dian) {
            $cufe_dian = reprocesarFacturaDian($tabla, $factura, $resolucion);
        }

        if (!$cufe_dian['cufe'] && $reprocesar_dian && !empty($cufe_dian['reprocesado'])) {
            $cufe_dian = obtenerCufeDesdeDian($resolucion['resolution_id'], $nombre_archivo);
        }

        if (!$cufe_dian['cufe']) {
            $resumen['sin_cufe']++;
            $resumen['errores'][] = [
                'id' => $factura['Id'],
                'codigo' => $factura['Codigo'],
                'error' => 'No se pudo obtener CUFE',
                'ultimo_xml' => $cufe_dian['origen'],
            ];
            escribirLog('sin_cufe', [
                'id' => $factura['Id'],
                'codigo' => $factura['Codigo'],
                'ultimo_xml' => $cufe_dian['origen'] ?? null,
                'detalle' => $cufe_dian['detalle'] ?? null,
                'respuesta' => $cufe_dian['respuesta'] ?? null,
                'error' => $cufe_dian['error'] ?? null,
                'resolution_id' => $resolucion['resolution_id'],
                'nombre_archivo' => $nombre_archivo,
                'reprocesar_dian' => $reprocesar_dian,
            ]);
            continue;
        }

        $qr = generarQrParaCufe($cufe_dian['cufe']);
        $update = "UPDATE $tabla SET Cufe = '" . $cufe_dian['cufe'] . "'";
        if ($qr) {
            $update .= ", Codigo_Qr = '$qr'";
        }
        $update .= " WHERE $tabla_pk = " . (int)$factura['Id'] . " AND (Cufe IS NULL OR TRIM(Cufe) = '')";

        $oUpdate = new consulta();
        $oUpdate->setQuery($update);
        $oUpdate->getData();

        $resumen['actualizadas']++;
        escribirLog('actualizada', ['id' => $factura['Id'], 'codigo' => $factura['Codigo'], 'cufe' => $cufe_dian['cufe']]);
        $resumen['registros'][] = [
            'id' => $factura['Id'],
            'codigo' => $factura['Codigo'],
            'cufe' => $cufe_dian['cufe'],
            'xml' => $cufe_dian['origen'],
        ];
    } catch (\Throwable $th) {
        $resumen['errores'][] = [
            'id' => $factura['Id'],
            'codigo' => $factura['Codigo'],
            'error' => $th->getMessage(),
        ];
        escribirLog('factura_error', ['id' => $factura['Id'], 'codigo' => $factura['Codigo'], 'error' => $th->getMessage()]);
        continue;
    }
        $ultimo_id = max($ultimo_id, (int)$factura['Id']);
    }
} while (!empty($facturas));

echo json_encode($resumen);

function validarFecha($fecha)
{
    if (!$fecha) {
        return false;
    }
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    return $d && $d->format('Y-m-d') === $fecha;
}

function normalizarNit($nit)
{
    $nit = explode('-', $nit)[0];
    return preg_replace('/[^0-9]/', '', $nit);
}

function obtenerConfiguracion()
{
    $oCon = new consulta();
    $oCon->setQuery("SELECT * FROM Configuracion WHERE Id_Configuracion = 1");
    $configuracion = $oCon->getData();
    unset($oCon);
    return $configuracion;
}

function obtenerFacturasSinCufe($tabla, $pk, $campo_fecha, $fecha_inicio, $fecha_fin, $solo_sin_cufe, $ultimo_id, $lote)
{
    $filtro_cufe = '';
    if ($solo_sin_cufe) {
        $filtro_cufe = " AND (Cufe IS NULL OR TRIM(Cufe) = '')";
    }
    $filtro_id = '';
    if ($ultimo_id) {
        $filtro_id = " AND $pk > " . (int)$ultimo_id;
    }
    $query = "SELECT $pk AS Id, Codigo, Cufe, Id_Resolucion, $campo_fecha AS Fecha 
        FROM $tabla 
        WHERE DATE($campo_fecha) BETWEEN '$fecha_inicio' AND '$fecha_fin'"
        . $filtro_cufe
        . $filtro_id
        . " ORDER BY $pk ASC LIMIT " . (int)$lote;
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $data = $oCon->getData();
    unset($oCon);
    return is_array($data) ? $data : [];
}

function obtenerResolucion($id_resolucion)
{
    static $cache = [];
    if (isset($cache[$id_resolucion])) {
        return $cache[$id_resolucion];
    }
    $oCon = new consulta();
    $oCon->setQuery("SELECT * FROM Resolucion WHERE Id_Resolucion = " . (int)$id_resolucion . " LIMIT 1");
    $data = $oCon->getData();
    unset($oCon);
    if ($data) {
        $cache[$id_resolucion] = $data;
    }
    return $data ?: null;
}

function construirNombreFactura($codigo_factura, $prefijo_resolucion, $nit, $fecha_documento)
{
    $codigo = (int)str_replace($prefijo_resolucion, '', $codigo_factura);
    // Se replica la lógica usada al generar la factura (año actual, no el de la fecha del documento)
    $anio = date('y');
    return str_pad($nit, 10, "0", STR_PAD_LEFT) . "000" . $anio . str_pad($codigo, 8, "0", STR_PAD_LEFT);
}

function obtenerCufeDesdeDian($resolution_id, $nombre_archivo)
{
    global $log_file;
    $bases = [
        'https://api-dian.innovating.com.co/api-dian',
        'https://api-dian.sigesproph.com.co/api-dian',
        'https://api-dian.192.168.40.201/api-dian',
        // Rutas sin el prefijo api-dian (por si cambia la estructura)
        'https://api-dian.innovating.com.co',
        'https://api-dian.sigesproph.com.co',
        'https://api-dian.192.168.40.201',
    ];

    $ultimo_intento = null;
    $missing_xml = true;
    foreach ($bases as $base) {
        $url = rtrim($base, '/') . "/storage/app/xml/1/$resolution_id/fv$nombre_archivo.xml";
        $ultimo_intento = $url;
        $xml = leerXml($url);
        if ($xml && preg_match('/<cbc:UUID[^>]*>(.*?)<\\/cbc:UUID>/i', $xml, $match)) {
            $missing_xml = false;
            escribirLog('cufe_xml_ok', ['url' => $url, 'uuid' => trim($match[1])]);
            return ['cufe' => trim($match[1]), 'origen' => $url];
        }
        if ($xml === false) {
            escribirLog('cufe_xml_fail', ['url' => $url, 'error' => 'fetch_failed']);
        } else {
            $missing_xml = false;
            escribirLog('cufe_xml_fail', ['url' => $url, 'error' => 'uuid_not_found']);
        }
    }

    return ['cufe' => null, 'origen' => $ultimo_intento, 'missing_xml' => $missing_xml];
}

function leerXml($url)
{
    global $log_file;
    $context = stream_context_create([
        'http' => ['timeout' => 8],
        'https' => [
            'timeout' => 8,
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
    ]);
    $data = @file_get_contents($url, false, $context);
    if ($data === false) {
        $error = error_get_last();
        escribirLog('leer_xml_error', ['url' => $url, 'error' => $error['message'] ?? 'unknown']);
    }
    return $data;
}

function generarQrParaCufe($cufe)
{
    if (!$cufe) {
        return null;
    }
    $directorio = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/ARCHIVOS/FACTURACION_ELECTRONICA/';
    if (!is_dir($directorio)) {
        mkdir($directorio, 0777, true);
    }
    $url = 'https://catalogo-vpfe.dian.gov.co/Document/ShowDocumentToPublic/' . $cufe;
    return generarqrFE($url);
}

function reprocesarFacturaDian($tabla, $factura, $resolucion)
{
    $resp = ['cufe' => null, 'origen' => 'reproceso', 'reprocesado' => true];
    try {
        escribirLog('reproceso_inicio', ['id' => $factura['Id'], 'codigo' => $factura['Codigo']]);
        $fe = new FacturaElectronica($tabla, $factura['Id'], $factura['Id_Resolucion']);
        $resultado = $fe->GenerarFactura();
        if (isset($resultado['Datos']['Cufe']) && $resultado['Datos']['Cufe']) {
            $resp['cufe'] = $resultado['Datos']['Cufe'];
        } elseif (isset($resultado['Cufe']) && $resultado['Cufe']) {
            $resp['cufe'] = $resultado['Cufe'];
        }
        if (isset($resultado['Respuesta'])) {
            $resp['respuesta'] = $resultado['Respuesta'];
        }
        escribirLog('reproceso_fin', ['id' => $factura['Id'], 'codigo' => $factura['Codigo'], 'cufe' => $resp['cufe'], 'respuesta' => $resp['respuesta'] ?? null]);
    } catch (\Throwable $th) {
        $resp['error'] = $th->getMessage();
        escribirLog('reproceso_error', ['id' => $factura['Id'], 'codigo' => $factura['Codigo'], 'error' => $th->getMessage()]);
    }
    return $resp;
}

function asegurarLogPath($ruta)
{
    $dir = dirname($ruta);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

function escribirLog($evento, $datos = [])
{
    global $log_file;
    if (!$log_file) {
        return;
    }
    $linea = date('c') . ' | ' . $evento . ' | ' . json_encode($datos, JSON_UNESCAPED_SLASHES) . PHP_EOL;
    file_put_contents($log_file, $linea, FILE_APPEND);
}
