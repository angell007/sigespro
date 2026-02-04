<?php
header('Content-Type: text/plain; charset=utf-8');

$accountCode = isset($_GET['accountCode']) ? trim($_GET['accountCode']) : '';
$accountCodeT = isset($_GET['accountCodeT']) ? trim($_GET['accountCodeT']) : '';
$softwareCode = isset($_GET['softwareCode']) ? trim($_GET['softwareCode']) : '';
$codigo = isset($_GET['codigo']) ? trim($_GET['codigo']) : '';
$resolucion_num = isset($_GET['resolucion']) ? trim($_GET['resolucion']) : '';

if ($accountCode === '' || $accountCodeT === '' || $softwareCode === '') {
    echo "Uso:\n";
    echo "  /sigespro-backend/php/consultar_clave_tecnica.php?accountCode=...&accountCodeT=...&softwareCode=...\n";
    echo "\n";
    echo "Opcionales:\n";
    echo "  &host=https://api-dian.sigesproph.com.co\n";
    echo "  &login=facturacion@prohsa.com\n";
    echo "  &password=804016084\n";
    echo "  &codigo=FESF\n";
    echo "  &resolucion=18764103230477\n";
    exit(1);
}

$host = isset($_GET['host']) && $_GET['host'] !== '' ? rtrim($_GET['host'], '/') : 'https://api-dian.sigesproph.com.co';
$login = isset($_GET['login']) && $_GET['login'] !== '' ? $_GET['login'] : 'facturacion@prohsa.com';
$password = isset($_GET['password']) && $_GET['password'] !== '' ? $_GET['password'] : '804016084';

$url = $host . '/api/ubl2.1/numbering/range/' .
    rawurlencode($accountCode) . '/' .
    rawurlencode($accountCodeT) . '/' .
    rawurlencode($softwareCode);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_SSLVERSION, 4);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, '');
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_VERBOSE, false);

$headers = array(
    "Content-type: application/json",
    "Accept: application/json",
    "Cache-Control: no-cache",
    "Authorization: Basic " . base64_encode($login . ':' . $password),
    "Pragma: no-cache",
    "Content-length: 0",
);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$result = curl_exec($ch);
$http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    echo "Error cURL: " . curl_error($ch) . "\n";
    curl_close($ch);
    exit(1);
}

curl_close($ch);

echo "URL: " . $url . "\n";
echo "HTTP: " . $http_code . "\n\n";

$json = json_decode($result, true);
if ($json === null) {
    echo "Respuesta cruda:\n";
    echo $result . "\n";
    exit(0);
}

echo "Respuesta JSON:\n";
echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

$keys = array(
    'technical_key',
    'technicalKey',
    'TechnicalKey',
    'ClaveTecnica',
    'Clave_Tecnica',
    'clave_tecnica',
);

$found = array();

function buscarClaveTecnica($data, $keys, &$found)
{
    if (is_array($data)) {
        foreach ($data as $k => $v) {
            if (is_string($k) && in_array($k, $keys, true)) {
                $found[] = array('key' => $k, 'value' => $v);
            }
            buscarClaveTecnica($v, $keys, $found);
        }
    }
}

buscarClaveTecnica($json, $keys, $found);

if (!empty($found)) {
    echo "Clave tecnica encontrada:\n";
    foreach ($found as $item) {
        echo "- " . $item['key'] . ": " . $item['value'] . "\n";
    }
} else {
    echo "No se encontro una clave tecnica en la respuesta.\n";
}

function recogerResoluciones($data, &$items)
{
    if (!is_array($data)) {
        return;
    }
    if (array_keys($data) !== range(0, count($data) - 1)) {
        $items[] = $data;
    }
    foreach ($data as $v) {
        recogerResoluciones($v, $items);
    }
}

if ($codigo !== '' || $resolucion_num !== '') {
    $items = array();
    recogerResoluciones($json, $items);
    $matches = array();
    foreach ($items as $item) {
        $itemCodigo = '';
        $itemResolucion = '';
        foreach ($item as $k => $v) {
            if (!is_string($k)) {
                continue;
            }
            if (strcasecmp($k, 'Codigo') === 0 || strcasecmp($k, 'prefix') === 0) {
                $itemCodigo = (string) $v;
            }
            if (strcasecmp($k, 'Resolucion') === 0 || strcasecmp($k, 'resolution') === 0) {
                $itemResolucion = (string) $v;
            }
        }
        if ($codigo !== '' && $itemCodigo !== '' && $itemCodigo !== $codigo) {
            continue;
        }
        if ($resolucion_num !== '' && $itemResolucion !== '' && $itemResolucion !== $resolucion_num) {
            continue;
        }
        if ($itemCodigo !== '' || $itemResolucion !== '') {
            $matches[] = $item;
        }
    }
    echo "\n";
    if (!empty($matches)) {
        echo "Coincidencias por filtro:\n";
        echo json_encode($matches, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    } else {
        echo "No se encontraron coincidencias con los filtros.\n";
    }
}
