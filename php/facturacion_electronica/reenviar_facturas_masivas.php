<?php

ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once($_SERVER['DOCUMENT_ROOT'] . '/config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/class.qr.php');
require_once('../../class/class.php_mailer.php');
include_once($MY_CLASS . "class.facturacion_electronica.php");

if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
}

$tipo = isset($_REQUEST['tipo']) ? $_REQUEST['tipo'] : 'Factura_Venta';
$res = isset($_REQUEST['res']) ? (int) $_REQUEST['res'] : 54;
$like = isset($_REQUEST['like']) ? $_REQUEST['like'] : 'FESF';
$limit = isset($_REQUEST['limit']) ? (int) $_REQUEST['limit'] : 50;
$offset = isset($_REQUEST['offset']) ? (int) $_REQUEST['offset'] : 0;
$dry = isset($_REQUEST['dry']) && $_REQUEST['dry'] == '1';
$sleep_ms = isset($_REQUEST['sleep_ms']) ? (int) $_REQUEST['sleep_ms'] : 0;
$email_override = isset($_REQUEST['email']) ? trim($_REQUEST['email']) : '';

if ($limit <= 0) {
    $limit = 50;
}
if ($offset < 0) {
    $offset = 0;
}

$like = preg_replace('/[^A-Za-z0-9_-]/', '', $like);
$like_sql = $like !== '' ? '%' . $like . '%' : '%';

$id_field = 'Id_Factura_Venta';
$tabla = 'Factura_Venta';
if ($tipo === 'Factura') {
    $id_field = 'Id_Factura';
    $tabla = 'Factura';
} elseif ($tipo === 'Factura_Capita') {
    $id_field = 'Id_Factura_Capita';
    $tabla = 'Factura_Capita';
} elseif ($tipo === 'Factura_Administrativa') {
    $id_field = 'Id_Factura_Administrativa';
    $tabla = 'Factura_Administrativa';
}

$query = "SELECT $id_field AS Id, Codigo
          FROM $tabla
          WHERE Id_Resolucion = $res
            AND Codigo LIKE '$like_sql'
          ORDER BY $id_field
          LIMIT $limit OFFSET $offset";

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$rows = $oCon->getData();
unset($oCon);

$resultado = [
    'total' => is_array($rows) ? count($rows) : 0,
    'procesadas' => 0,
    'enviadas' => 0,
    'dry_run' => $dry ? 1 : 0,
    'errores' => []
];

if (!is_array($rows)) {
    echo json_encode($resultado);
    exit;
}

foreach ($rows as $row) {
    $resultado['procesadas']++;
    $id_factura = (int) $row['Id'];
    $codigo = isset($row['Codigo']) ? $row['Codigo'] : '';

    if ($dry) {
        $resultado['enviadas']++;
        continue;
    }

    try {
        $fe = new FacturaElectronica($tipo, $id_factura, $res);
        $resp = $fe->ReenviarCorreoExistente($email_override);
        if (is_array($resp) && isset($resp['Estado']) && $resp['Estado'] === 'Exito') {
            $resultado['enviadas']++;
        } else {
            $resultado['errores'][] = [
                'id' => $id_factura,
                'codigo' => $codigo,
                'error' => is_array($resp) && isset($resp['Respuesta']) ? $resp['Respuesta'] : 'Error enviando correo'
            ];
        }
    } catch (Exception $e) {
        $resultado['errores'][] = [
            'id' => $id_factura,
            'codigo' => $codigo,
            'error' => $e->getMessage()
        ];
    }

    if ($sleep_ms > 0) {
        usleep($sleep_ms * 1000);
    }
}

echo json_encode($resultado);
