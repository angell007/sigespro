<?php
// Genera un XLSX con auditor¨ªa MiPres (no inserta en BD).
// Uso:
//  - Rango: php php/auditoria_mipres_excel.php --desde 2025-11-01 --hasta 2025-11-30 [--mun 25307] [--eps 900226715] [--estado aceptado|rechazado|todos]
//  - Prescripci¨®n puntual: php php/auditoria_mipres_excel.php --prescripcion 20251125157002805042 [--eps 900226715] [--estado aceptado|rechazado|todos]
//  - Por URL: https://sigesproph.com.co/php/auditoria_mipres_excel.php?fecha=2025-11-30&estado=todos&mun=25307&eps=900226715

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: text/plain');

include_once __DIR__ . '/../class/class.querybasedatos.php';
include_once __DIR__ . '/../class/class.complex.php';
include_once __DIR__ . '/../class/class.mipres.php';
require_once __DIR__ . '/../class/PHPExcel.php';
require_once __DIR__ . '/../class/PHPExcel/IOFactory.php';

$args = getopt('', [
    'fecha::',
    'desde::',
    'hasta::',
    'prescripcion::',
    'mun::',
    'eps::',
    'estado::',
]);

$isCli = PHP_SAPI === 'cli';
$requestParams = $_REQUEST ?? [];
$getParam = function ($key, $default = null) use ($args, $requestParams) {
    if (isset($args[$key]) && $args[$key] !== '') {
        return trim($args[$key]);
    }
    if (isset($requestParams[$key]) && $requestParams[$key] !== '') {
        return trim($requestParams[$key]);
    }
    return $default;
};

$fecha = $getParam('fecha', date('Y-m-d'));
$desde = $getParam('desde');
$hasta = $getParam('hasta');
$prescripcion = $getParam('prescripcion');
$municipioFiltro = $getParam('mun');
$epsFiltro = $getParam('eps');
$estadoFiltro = strtolower($getParam('estado', 'todos'));
if (!in_array($estadoFiltro, ['aceptado', 'rechazado'], true)) {
    $estadoFiltro = 'todos';
}

$mipres = new Mipres();
$rows = [];
$fechas = [];
$tag = 'unico';

if ($prescripcion) {
    $tag = "prescripcion_{$prescripcion}";
    $lotes = [
        [
            'tag' => "Prescripcion $prescripcion",
            'datos' => $mipres->GetDireccionamientoPorPrescripcion($prescripcion),
        ],
    ];
} else {
    if ($desde && $hasta) {
        $tag = "{$desde}_{$hasta}";
        for ($ts = strtotime($desde); $ts <= strtotime($hasta); $ts = strtotime('+1 day', $ts)) {
            $fechas[] = date('Y-m-d', $ts);
        }
    } else {
        $fechas[] = $fecha;
        $tag = $fecha;
    }

    $lotes = [];
    foreach ($fechas as $f) {
        $lotes[] = [
            'tag' => "Fecha $f",
            'datos' => $mipres->GetDireccionamientoPorFecha($f),
        ];
    }
}

foreach ($lotes as $lote) {
    $direccionamientos = normalizarDireccionamientos($lote['datos']);
    if (empty($direccionamientos)) {
        continue;
    }

    foreach ($direccionamientos as $dis) {
        if ($municipioFiltro && $dis['CodMunEnt'] != $municipioFiltro) {
            continue;
        }
        if ($epsFiltro && $dis['NoIDEPS'] != $epsFiltro) {
            continue;
        }

        $fallas = [];
        $paciente = getPaciente($dis['NoIDPaciente']);
        $mun = getMunicipio($dis['CodMunEnt']);
        $epsNombre = getEpsNombre($dis['NoIDEPS'], $paciente['EPS'] ?? null);

        if (!$paciente) {
            $fallas[] = 'Paciente no existe';
        }
        if (!validarMunicipio($dis['CodMunEnt'], $dis['NoIDEPS'])) {
            $fallas[] = 'Sin punto activo para municipio/EPS';
        }
        $cum = normalizarCum($dis['CodSerTecAEntregar']);
        $idProducto = $cum ? getIdProductoAsociado($cum, $dis['TipoTec']) : null;
        if (!$cum) {
            $fallas[] = 'CodSerTecAEntregar con prefijo cum no soportado';
        } elseif (!$idProducto) {
            $fallas[] = "CUM $cum sin asociaci¨®n de tecnolog¨ªa";
        }

        $estadoAuditoria = $fallas ? 'Rechazado' : 'Aceptado';
        if ($estadoFiltro !== 'todos' && strtolower($estadoAuditoria) !== $estadoFiltro) {
            continue;
        }

        $rows[] = [
            'NoPrescripcion' => $dis['NoPrescripcion'],
            'IDDireccionamiento' => $dis['IDDireccionamiento'] ?? ($dis['ID'] ?? ''),
            'NoEntrega' => $dis['NoEntrega'],
            'PacienteID' => $dis['NoIDPaciente'],
            'PacienteNombre' => $paciente['Nombre'] ?? '',
            'EPSNit' => $dis['NoIDEPS'],
            'EPSNombre' => $epsNombre,
            'MunicipioCod' => $dis['CodMunEnt'],
            'MunicipioNombre' => $mun['Nombre'] ?? '',
            'FechaDireccionamiento' => $dis['FecDireccionamiento'],
            'FechaMaxEntrega' => $dis['FecMaxEnt'],
            'CantTotAEntregar' => $dis['CantTotAEntregar'],
            'TipoTec' => $dis['TipoTec'],
            'CUM' => $cum,
            'Estado' => $estadoAuditoria,
            'Fallas' => implode('; ', $fallas),
        ];
    }
}

generarExcel($rows, $tag);

// Helpers
function normalizarCum($cod)
{
    $partes = explode('-', $cod);
    if ($partes[0] === 'cum') {
        return null;
    }
    return str_pad((int) $partes[0], 2, '0', STR_PAD_LEFT);
}

function getPaciente($id)
{
    if ($id === '') {
        return null;
    }
    $q = new consulta();
    $q->setQuery("SELECT Id_Paciente, CONCAT_WS(' ', Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido) AS Nombre, EPS FROM Paciente WHERE Id_Paciente='$id' LIMIT 1");
    $data = $q->getData();
    return $data ?: null;
}

function getMunicipio($codigo)
{
    $q = new consulta();
    $q->setQuery("SELECT Codigo, Nombre, Id_Departamento FROM Municipio WHERE Codigo='$codigo' LIMIT 1");
    $data = $q->getData();
    return $data ?: null;
}

function getEpsNombre($nit, $fallback = null)
{
    if ($fallback) {
        return $fallback;
    }
    $q = new consulta();
    $q->setQuery("SELECT IFNULL(Nombre, Razon_Social) AS Nombre FROM Cliente WHERE Id_Cliente='$nit' LIMIT 1");
    $data = $q->getData();
    return $data ? $data['Nombre'] : '';
}

function validarMunicipio($codigo, $nit)
{
    $q = new consulta();
    $q->setQuery("SELECT Id_Departamento FROM Municipio WHERE Codigo='$codigo'");
    $mun = $q->getData();
    if (!$mun) {
        return false;
    }

    $q = new consulta();
    $q->setQuery("SELECT Id_Punto_Dispensacion FROM Punto_Dispensacion WHERE Departamento={$mun['Id_Departamento']} AND Tipo_Dispensacion='Entrega'");
    $punto = $q->getData();
    if (!$punto) {
        return false;
    }

    $q = new consulta();
    $q->setQuery("SELECT Id_Punto_Dispensacion FROM Punto_Cliente WHERE Id_Cliente=$nit AND Id_Punto_Dispensacion={$punto['Id_Punto_Dispensacion']}");
    $c = $q->getData();
    return (bool) $c;
}

function getIdProductoAsociado($cum, $tec)
{
    $q = new consulta();
    $q->setQuery("SELECT Id_Producto FROM Producto_Tipo_Tecnologia_Mipres PD INNER JOIN Tipo_Tecnologia_Mipres M ON PD.Id_Tipo_Tecnologia_Mipres=M.Id_Tipo_Tecnologia_Mipres WHERE (Codigo_Actual='$cum' OR Codigo_Anterior='$cum') AND M.Codigo='$tec' LIMIT 1");
    $data = $q->getData();
    return $data['Id_Producto'] ?? null;
}

function normalizarDireccionamientos($datos)
{
    if (!$datos) {
        return [];
    }

    // Respuesta de un solo direccionamiento como arreglo asociativo
    if (isset($datos['NoPrescripcion'])) {
        return [$datos];
    }

    // Si es un arreglo indexado, devolver tal cual
    if (is_array($datos) && array_keys($datos) === range(0, count($datos) - 1)) {
        return $datos;
    }

    // Fallback: intentar convertir a arreglo indexado
    return (array) $datos;
}

function generarExcel($rows, $tag)
{
    $objPHPExcel = new PHPExcel();
    $sheet = $objPHPExcel->setActiveSheetIndex(0);
    $headers = [
        'NoPrescripcion',
        'IDDireccionamiento',
        'NoEntrega',
        'PacienteID',
        'PacienteNombre',
        'EPSNit',
        'EPSNombre',
        'MunicipioCod',
        'MunicipioNombre',
        'FechaDireccionamiento',
        'FechaMaxEntrega',
        'CantTotAEntregar',
        'TipoTec',
        'CUM',
        'Estado',
        'Fallas',
    ];
    $col = 0;
    foreach ($headers as $h) {
        $sheet->setCellValueByColumnAndRow($col, 1, $h);
        $col++;
    }

    $rowNum = 2;
    foreach ($rows as $r) {
        $col = 0;
        foreach ($headers as $h) {
            $sheet->setCellValueByColumnAndRow($col, $rowNum, $r[$h] ?? '');
            $col++;
        }
        $rowNum++;
    }

    foreach (range(0, count($headers) - 1) as $c) {
        $sheet->getColumnDimensionByColumn($c)->setAutoSize(true);
    }

    $file = __DIR__ . "/../tmp/auditoria_mipres_{$tag}.xlsx";
    $writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $writer->save($file);

    echo "Generado: $file\n";
}
