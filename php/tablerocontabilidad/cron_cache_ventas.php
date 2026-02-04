<?php

require_once __DIR__ . '/../../config/start.inc.php';
include_once __DIR__ . '/../../class/class.consulta.php';
include_once __DIR__ . '/services/ReportCache.php';
include_once __DIR__ . '/services/VentasReportService.php';

function GetDatos($query)
{
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $datos = $oCon->getData();
    unset($oCon);
    return $datos;
}

$isCli = php_sapi_name() === 'cli';
if ($isCli) {
    $options = getopt('', ['fini:', 'ffin:', 'nit::']);
    if (!isset($options['fini']) || !isset($options['ffin'])) {
        echo "Uso: php cron_cache_ventas.php --fini=YYYY-MM-DD --ffin=YYYY-MM-DD [--nit=123]\n";
        exit(1);
    }
    $request = [
        'fini' => $options['fini'],
        'ffin' => $options['ffin']
    ];
    if (isset($options['nit']) && $options['nit'] !== '') {
        $request['nit'] = $options['nit'];
    }
} else {
    $fini = isset($_GET['fini']) ? $_GET['fini'] : '';
    $ffin = isset($_GET['ffin']) ? $_GET['ffin'] : '';
    if ($fini === '' || $ffin === '') {
        http_response_code(400);
        echo "Falta fini o ffin";
        exit(1);
    }
    $request = [
        'fini' => $fini,
        'ffin' => $ffin
    ];
    if (isset($_GET['nit']) && $_GET['nit'] !== '') {
        $request['nit'] = $_GET['nit'];
    }
}

$condicion_nit = '';
if (isset($request['nit']) && $request['nit'] !== '') {
    $condicion_nit = " AND F.Id_Cliente=" . $request['nit'];
}

$ventasService = new VentasReportService();
$queries = $ventasService->buildQueries($request, $condicion_nit);
$cacheDir = dirname(__DIR__, 2) . '/storage/reportes_cache';
$cache = new ReportCache($cacheDir, 300);
$cachePrefix = $ventasService->getCachePrefix($request);

foreach ($queries as $indiceQuery => $q) {
    $cacheKey = $cache->keyForQuery($cachePrefix . '_q' . $indiceQuery, $q);
    $datos = GetDatos($q);
    if (is_array($datos) && !empty($datos)) {
        $cache->set($cacheKey, $datos);
    }
}

echo "Cache de Ventas actualizado para {$request['fini']} a {$request['ffin']}\n";
