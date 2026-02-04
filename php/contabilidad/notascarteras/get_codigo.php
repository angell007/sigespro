<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');
require_once('../../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */
require('../../comprobantes/funciones.php');

$mes = isset($_REQUEST['Fecha']) && $_REQUEST['Fecha'] != '' ? date('m', strtotime($_REQUEST['Fecha'])) : date('m');
$anio = isset($_REQUEST['Fecha']) && $_REQUEST['Fecha'] != '' ? date('Y', strtotime($_REQUEST['Fecha'])) : date('Y');
$tipo = $_REQUEST['Tipo'];

switch ($tipo) {
    case 'Debito-Cliente':
        $tipo = 'Nota Debito Cliente';
        break;
    
    case 'Credito-Cliente':
        $tipo = 'Nota Credito Cliente';
        break;
    case 'Debito-Proveedor':
        $tipo = 'Nota Debito Proveedor';
        break;
    case 'Credito-Proveedor':
        $tipo = 'Nota Credito Proveedor';
        break;
}

$consecutivo = obtenerProximoConsecutivo($tipo, $mes, $anio);

echo json_encode([
    "consecutivo" => $consecutivo
]);

?>