<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

if (!defined('FE_REPROCESO')) {
    define('FE_REPROCESO', true);
}
if (!defined('FE_DEBUG_DIAN')) {
    define('FE_DEBUG_DIAN', true);
}
if (!defined('FE_USAR_FECHA_ACTUAL')) {
    define('FE_USAR_FECHA_ACTUAL', true);
}
if (!defined('FE_USAR_FECHA_ACTUAL') && isset($_REQUEST['usar_fecha_actual'])) {
    $raw = strtolower(trim((string) $_REQUEST['usar_fecha_actual']));
    $usarFechaActual = false;
    if ($raw !== '') {
        $usarFechaActual = in_array($raw, ['1', 'true', 'si', 'yes', 'actual'], true);
        if (!$usarFechaActual && is_numeric($raw)) {
            $usarFechaActual = ((int) $raw) > 0;
        }
    }
    if ($usarFechaActual) {
        define('FE_USAR_FECHA_ACTUAL', true);
    }
}

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.facturacion_electronica.php');

$tipo = isset($_REQUEST['tipo']) ? $_REQUEST['tipo'] : 'Factura';
$res = isset($_REQUEST['res']) ? $_REQUEST['res'] : null;
$fecha_inicio = isset($_REQUEST['fecha_inicio']) ? $_REQUEST['fecha_inicio'] : null;
$id_factura = isset($_REQUEST['id_factura']) ? (int) $_REQUEST['id_factura'] : null;
$facturas = GetFacturas($tipo, $res, $fecha_inicio, $id_factura);

if ($id_factura) {
    $factura = !empty($facturas) ? $facturas[0] : null;
    if (!$factura) {
        http_response_code(404);
        echo json_encode([
            "error" => "No se encontró la factura especificada",
            "mensaje" => "No se encontró la factura especificada",
        ]);
        exit;
    }

    // Solo se procesa la factura si existe y cumple con la condición
    if (contarCodigo($tipo, $factura['Codigo']) == '1') {
        $fe = new FacturaElectronica($tipo, $factura["Id_Factura"], $factura["Id_Resolucion"]);
        $datos = $fe->GenerarFactura();
        if (isset($datos['Estado']) && $datos['Estado'] === 'Exito') {
            $resp['titulo'] = 'Reproceso Exitoso';
            $resp['mensaje'] = isset($datos['Detalles']) ? $datos['Detalles'] : 'Factura reprocesada correctamente.';
            $resp['tipo'] = 'success';
        } else {
            $resp['titulo'] = 'Error en reproceso';
            if (isset($datos['Detalles']) && $datos['Detalles']) {
                $resp['mensaje'] = $datos['Detalles'];
            } elseif (isset($datos['Respuesta']) && $datos['Respuesta']) {
                $resp['mensaje'] = $datos['Respuesta'];
            } else {
                $resp['mensaje'] = 'No se pudo reprocesar la factura.';
            }
            $resp['tipo'] = 'error';
        }
        $resp['data'] = $datos;
    } else {
        http_response_code(406);
        echo json_encode([
            "error" => "La factura no cumple con las condiciones para ser reprocesada",
            "mensaje" => "La factura no cumple con las condiciones para ser reprocesada",
        ]);
        exit;
    }

    echo json_encode($resp);
    exit;
}

    if (count($facturas) == 0) {
        echo json_encode([
            'respuesta' => [],
            'mensaje' => 'No hay facturas para reprocesar con los filtros enviados.',
        ]);
        return;
    }

    $resp = [];
    foreach ($facturas as $factura) {
        $fe = new FacturaElectronica($tipo, $factura["Id_Factura"], $factura["Id_Resolucion"]);
        $datos = $fe->GenerarFactura();
        if (!empty($datos['Datos']['Cufe']) && ($datos['Estado'] ?? '') === 'Exito') {
            actualizarFacturaElectronica($tipo, $factura["Id_Factura"], $datos);
        } elseif (debeMarcarProcesadaPorError($datos)) {
            marcarFacturaProcesadaPorError($tipo, $factura["Id_Factura"]);
        }
        $resp['respuesta'][] = $datos;
    }

    echo json_encode($resp);

    function GetFacturas($tipo, $res, $fecha_inicio, $id_factura)
    {
        $cond_res = (isset($res) ? "AND Id_Resolucion IN (" . $res . ")" : '');
    $cond_id = (isset($id_factura) ? "AND Id_" . $tipo . " = " . intval($id_factura) : '');
    $campo_fecha = GetCampoFechaFactura($tipo);
    $cond_fecha = (isset($fecha_inicio) ? "AND " . $campo_fecha . " >= '" . $fecha_inicio . "'" : '');

    $cond_procesada = '';
    if (!isset($id_factura)) {
        $cond_procesada = "AND (Procesada = 'false' OR Procesada IS NULL)";
    }

    $query = "SELECT Id_" . $tipo . " as Id_Factura, Codigo, Id_Resolucion FROM " . $tipo . " WHERE 
        1=1 $cond_procesada $cond_res $cond_id $cond_fecha
        ORDER BY " . $campo_fecha . " ASC";

        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo("Multiple");
        $lista = $oCon->getData();
        unset($oCon);

        return $lista;
    }

    function GetCampoFechaFactura($tipo)
    {
        if ($tipo === 'Factura_Venta') {
            return 'Fecha';
        }
        return 'Fecha_Documento';
    }

    function actualizarFacturaElectronica($tipo, $id_factura, $datos)
    {
        if (!$tipo || !$id_factura) {
            return;
        }
        if (($datos['Estado'] ?? '') !== 'Exito') {
            return;
        }
        $cufe = $datos['Datos']['Cufe'] ?? ($datos['Cufe'] ?? null);
        if (!$cufe) {
            return;
        }
        $qr = $datos['Datos']['Qr'] ?? ($datos['Qr'] ?? null);
        if (!$qr && $cufe) {
            $url = 'https://catalogo-vpfe.dian.gov.co/Document/ShowDocumentToPublic/' . $cufe;
            $qr = generarqrFE($url);
        }
        $oItem = new complex($tipo, "Id_" . $tipo, $id_factura);
        $oItem->Cufe = $cufe;
        if ($qr) {
            $oItem->Codigo_Qr = $qr;
        }
        $oItem->Procesada = 'true';
        $oItem->save();
        unset($oItem);
    }

    function debeMarcarProcesadaPorError($datos)
    {
        $mensajes = [];
        if (isset($datos['ErrorMessage'])) {
            $errorMessage = $datos['ErrorMessage'];
            if (is_array($errorMessage) && isset($errorMessage['string'])) {
                $mensajes = array_merge($mensajes, (array) $errorMessage['string']);
            } elseif (is_string($errorMessage)) {
                $mensajes[] = $errorMessage;
            }
        }
        if (!$mensajes) {
            return false;
        }
        $objetivos = [
            'Regla: FAJ43b, Notificación: Nombre informado No corresponde al registrado en el RUT con respecto al Nit suminstrado.',
            'Regla: RUT01, Notificación: La validación del estado del RUT próximamente estará disponible.',
        ];
        foreach ($mensajes as $mensaje) {
            if (in_array(trim((string) $mensaje), $objetivos, true)) {
                return true;
            }
        }
        return false;
    }

    function marcarFacturaProcesadaPorError($tipo, $id_factura)
    {
        if (!$tipo || !$id_factura) {
            return;
        }
        $oItem = new complex($tipo, "Id_" . $tipo, $id_factura);
        $oItem->Procesada = 'true';
        $oItem->save();
        unset($oItem);
    }

    function contarCodigo($tipo, $codigo)
    {
        $query = "SELECT COUNT(Id_$tipo) as Total 
     FROM $tipo 
     WHERE Codigo LIKE '$codigo'";
        $oCon = new consulta();
        $oCon->setQuery($query);
        return $oCon->getData()['Total'];
    }
