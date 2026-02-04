<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

$baseDir = dirname(__DIR__, 2);
require_once $baseDir . '/config/start.inc.php';
include_once $baseDir . '/class/class.lista.php';
include_once $baseDir . '/class/class.complex.php';
include_once $baseDir . '/class/class.consulta.php';

$notaClass = $baseDir . '/class/class.nota_credito_electronica.php';
if (file_exists($notaClass)) {
    require_once $notaClass;
}
$notaClassAlt = $baseDir . '/class/class.nota_credito_electronica_estructura.php';
if (!class_exists('NotaCreditoElectronica') && file_exists($notaClassAlt)) {
    require_once $notaClassAlt;
}
if (!class_exists('NotaCreditoElectronica')) {
    throw new Exception('Clase NotaCreditoElectronica no disponible.');
}

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('html_errors', 0);
error_reporting(E_ALL);

$tipo = (isset($_REQUEST['tipo']) ? $_REQUEST['tipo'] : null);
$id = (isset($_REQUEST['Id_Nota_Credito']) ? $_REQUEST['Id_Nota_Credito'] : (isset($_REQUEST['id_factura']) ? $_REQUEST['id_factura'] : null));
$resolucion = (isset($_REQUEST['res']) ? $_REQUEST['res'] : null); 



try {
    $facts = GetNotas($tipo, $id);
    if ($facts) {
        $respuestas = [];
        foreach ($facts as $nota) {
            $fe = new NotaCreditoElectronica($nota['Tipo'], $nota['Id'], $resolucion);
            if (method_exists($fe, 'getNombreArchivo') && method_exists($fe, 'getResolucionId')) {
                $nombre_archivo = $fe->getNombreArchivo();
                $resolution_id = $fe->getResolucionId();
                $xml_info = obtenerXmlNotaCredito($resolution_id, $nombre_archivo);
                if ($xml_info['xml']) {
                    $cude = extraerCudeDesdeXml($xml_info['xml']);
                    if ($cude) {
                        $datos = $fe->ProcesarCude($cude);
                        $datos["OrigenXml"] = $xml_info['origen'];
                    } else {
                        $datos = [
                            "Estado" => "Error",
                            "Detalles" => "No se encontr¨® CUDE en XML.",
                            "OrigenXml" => $xml_info['origen'],
                        ];
                    }
                } else {
                    $datos = $fe->GenerarNota();
                }
            } else {
                $datos = $fe->GenerarNota();
            }
            $respuestas[] = $datos;
        }
        echo json_encode(count($respuestas) === 1 ? $respuestas[0] : $respuestas);
        exit;
    } else {
        echo json_encode(['mensaje' => 'No se encontraron notas para procesar.']);
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'mensaje' => $e->getMessage(),
    ]);
}

function GetNotas($tipo, $id) {
    $query = '';
if ($tipo === 'Nota_Credito') {
    $query = 'SELECT Id_Nota_Credito AS Id, "Nota_Credito" AS Tipo, Codigo, Fecha 
              FROM Nota_Credito 
              WHERE Id_Nota_Credito = ' . intval($id) . ' AND DATE(Fecha) >= "2024-07-01" AND (Procesada IS NULL OR Procesada = FALSE)';
} elseif ($tipo === 'Nota_Credito_Global') {
    $query = 'SELECT Id_Nota_Credito_Global AS Id, "Nota_Credito_Global" AS Tipo, Codigo, Fecha 
              FROM Nota_Credito_Global 
              WHERE Id_Nota_Credito_Global = ' . intval($id) . ' AND DATE(Fecha) >= "2024-07-01" AND (Procesada IS NULL OR Procesada = FALSE)';
} else {
    throw new Exception('Tipo de nota no v¨¢lido.');
}


    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo("Multiple");
    $lista = $oCon->getData();
    unset($oCon);
    return $lista;
}

function obtenerXmlNotaCredito($resolution_id, $nombre_archivo) {
    $resultado = [
        'xml' => null,
        'origen' => null,
    ];
    if (!$resolution_id || !$nombre_archivo) {
        return $resultado;
    }

    $paths = [
        '/home/sigesproph/api-dian.sigesproph.com.co/api-dian/storage/app/xml/1/' . $resolution_id . '/nc' . $nombre_archivo . '.xml',
        '/home/sigesproph/api-dian.192.168.40.201/api-dian/storage/app/xml/1/' . $resolution_id . '/nc' . $nombre_archivo . '.xml',
        '/home/sigespro/api-dian.sigesproph.com.co/api-dian/storage/app/xml/1/' . $resolution_id . '/nc' . $nombre_archivo . '.xml',
        '/home/sigespro/api-dian.192.168.40.201/api-dian/storage/app/xml/1/' . $resolution_id . '/nc' . $nombre_archivo . '.xml',
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            $xml = file_get_contents($path);
            if ($xml !== false && $xml !== '') {
                $resultado['xml'] = $xml;
                $resultado['origen'] = $path;
                return $resultado;
            }
        }
    }

    $bases = [
        'https://api-dian.innovating.com.co/api-dian',
        'https://api-dian.sigespro.com.co/api-dian',
        'https://api-dian.sigesproph.com.co/api-dian',
        'https://api-dian.192.168.40.201/api-dian',
        'https://api-dian.innovating.com.co',
        'https://api-dian.sigespro.com.co',
        'https://api-dian.sigesproph.com.co',
        'https://api-dian.192.168.40.201',
    ];

    foreach ($bases as $base) {
        $url = rtrim($base, '/') . '/storage/app/xml/1/' . $resolution_id . '/nc' . $nombre_archivo . '.xml';
        $xml = leerXmlNotaCredito($url);
        if ($xml !== false && $xml !== '') {
            $resultado['xml'] = $xml;
            $resultado['origen'] = $url;
            return $resultado;
        }
        $resultado['origen'] = $url;
    }

    return $resultado;
}

function leerXmlNotaCredito($url) {
    $context = stream_context_create([
        'http' => ['timeout' => 8],
        'https' => [
            'timeout' => 8,
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
    ]);
    return @file_get_contents($url, false, $context);
}

function extraerCudeDesdeXml($xml) {
    if (!$xml) {
        return null;
    }
    if (preg_match('/<cbc:UUID[^>]*>(.*?)<\\/cbc:UUID>/i', $xml, $match)) {
        return trim($match[1]);
    }
    return null;
}
?>
