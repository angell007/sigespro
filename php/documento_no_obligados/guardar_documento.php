<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once '../../config/start.inc.php';
include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';
include_once('../../class/class.documento_soporte_electronico.php');

// include_once('../../class/class.contabilizar.php');

$logFile = __DIR__ . '/../../tmp/documento_no_obligados_guardar.log';

ini_set('log_errors', 1);

function logGuardarDocumento($mensaje, array $contexto = [])
{
    global $logFile;
    $fecha = date('Y-m-d H:i:s');
    $linea = "[$fecha] $mensaje";

    if (!empty($contexto)) {
        $linea .= ' | ' . json_encode($contexto, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    }

    error_log($linea . PHP_EOL, 3, $logFile);
}

set_exception_handler(function ($e) {
    logGuardarDocumento('Excepcion no controlada', [
        'mensaje' => $e->getMessage(),
        'archivo' => $e->getFile(),
        'linea' => $e->getLine(),
    ]);
    http_response_code(500);
    echo json_encode(['Titulo' => 'Error', 'Mensaje' => 'Error inesperado guardando el documento', 'Tipo' => 'error', 'Detalle' => $e->getMessage()]);
    exit;
});

set_error_handler(function ($severity, $message, $file, $line) {
    logGuardarDocumento('Error PHP', [
        'severidad' => $severity,
        'mensaje' => $message,
        'archivo' => $file,
        'linea' => $line,
    ]);
    return false;
});

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        logGuardarDocumento('Error fatal en shutdown', $error);
    }
});

$rawInput = file_get_contents('php://input');
logGuardarDocumento('Peticion recibida guardar_documento', [
    'metodo' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
    'request' => $_REQUEST,
    'raw' => $rawInput,
]);

try {
    $funcionario = (isset($_REQUEST['funcionario']) ? $_REQUEST['funcionario'] : '');
    $cliente = (isset($_REQUEST['cliente']) ? $_REQUEST['cliente'] : '');
    $total = (isset($_REQUEST['total']) ? (float) $_REQUEST['total'] : '');
    $centroCosto = (isset($_REQUEST['centroCosto']) ? $_REQUEST['centroCosto'] : '');
    $tipoCliente = (isset($_REQUEST['tipoCliente']) ? $_REQUEST['tipoCliente'] : '');

    $factura = (isset($_REQUEST['factura']) ? $_REQUEST['factura'] : '');
    $factura = json_decode($factura, true);
    if (!is_array($factura)) {
        throw new RuntimeException('La factura no se pudo decodificar o viene vacia');
    }

    $productos = (isset($_REQUEST['productos']) ? $_REQUEST['productos'] : '');
    $productos = json_decode($productos, true);
    if (!is_array($productos)) {
        throw new RuntimeException('Los productos no se pudieron decodificar o vienen vacios');
    }

    $resolucion = getResolucion();
    $consecutivo = GenerarConsecutivo($resolucion);

    if (!isset($consecutivo)) {
        $result['Mensaje'] = "No tiene una resolucion Activa, no se puede guardar";
        $result['Titulo'] = "Error de resolucion";
        $result['Tipo'] = "error";
        logGuardarDocumento('Falta resolucion activa', ['resolucion' => $resolucion]);
        echo json_encode($result);exit;
    }

    $oItem = new complex('Documento_No_Obligados', 'Id_Documento_No_Obligados');
    $oItem->Id_Funcionario = $funcionario;
    $oItem->Codigo = $consecutivo;
    $oItem->Id_Proveedor = $factura['Proveedor']['Id_Cliente'] ?? null;
    $oItem->Tipo_Proveedor = $factura['Tipo_Proveedor'] ?? null;
    $oItem->Tipo_Reporte = $factura['PeriodoTransmision'] ?? null;
    $oItem->Fecha_Adquirido = $factura['FechaAdquirido'] ?? null;
    $oItem->Id_Resolucion = $resolucion['Id_Resolucion'];
    $oItem->Fecha_Documento = date('Y-m-d H:i:s');
    $oItem->Observaciones = $factura['Observaciones'] ?? '';
    $oItem->Forma_Pago = isset($factura['FormaPago']) && $factura['FormaPago'] == 'Contado' ? 1 : 2;
    $oItem->Vencimiento_Pago = $factura['VencimientoPago'] ?? null;
    $oItem->save();
    $id_Documento = $oItem->getId();
    if($id_Documento){

        guardarDescripciones($productos);
        try {
            $documento_electronico = new DocumentoElectronico($id_Documento, $resolucion['Id_Resolucion']);
            $json = $documento_electronico->getJson();
            $datos_dian = $documento_electronico->GenerarDocumento();
        } catch (\Throwable $th) {
            logGuardarDocumento('Error creando DocumentoElectronico', ['mensaje' => $th->getMessage()]);
            throw $th;
        }
        
        $result['Titulo']='Exito';
        $result['Mensaje']='Guardado correctamente el documento '.$consecutivo;
        $result['Tipo']='success';
        $result['Json']=$json;
        if (isset($datos_dian['Estado']) && $datos_dian['Estado'] === 'Exito') {
            $result['Dian'] = [
                'titulo' => 'Reproceso Exitoso',
                'mensaje' => isset($datos_dian['Detalles']) ? $datos_dian['Detalles'] : 'Documento procesado correctamente.',
                'tipo' => 'success',
                'data' => $datos_dian,
            ];
        } else {
            $result['Dian'] = [
                'titulo' => 'Error en reproceso',
                'mensaje' => isset($datos_dian['Detalles']) && $datos_dian['Detalles'] ? $datos_dian['Detalles'] : 'No se pudo procesar el documento.',
                'tipo' => 'error',
                'data' => $datos_dian,
            ];
        }
        $result['Id_Factura']=$id_Documento;
        logGuardarDocumento('Documento guardado con exito', [
            'codigo' => $consecutivo,
            'id_documento' => $id_Documento,
            'factura' => $factura,
        ]);
    }else{
        $result['Titulo']='Errir';
        $result['Mensaje']='Ha ocurrido un error al guardar el documento electronico';
        $result['Tipo']='warning';
        logGuardarDocumento('Error guardando documento', ['factura' => $factura]);

    }

    unset($oItem);

    echo json_encode($result);
} catch (\Throwable $th) {
    http_response_code(500);
    $result = [
        'Titulo' => 'Error',
        'Mensaje' => 'No se pudo guardar el documento',
        'Tipo' => 'error',
        'Detalle' => $th->getMessage(),
    ];
    logGuardarDocumento('Excepcion capturada guardar_documento', [
        'mensaje' => $th->getMessage(),
        'traza' => $th->getTraceAsString(),
    ]);
    echo json_encode($result);
}

function guardarDescripciones($productos)
{
    global $id_Documento;
    foreach ($productos as $key => $prod) {
        $oProd = new complex('Descripcion_Documento_No_Obligados', 'Id_Descripcion_Documento_No_Obligados');
        $oProd->Id_Documento_No_Obligados = $id_Documento;

        foreach ($prod as $key => $value) {
            if ($value !== '') {
                $oProd->$key = $value;
            }

        }
        $oProd->Codigo_Producto_Servicio = $prod['Codigo'];
        $oProd->Paquete_Cantidad = $prod['Pack_Size']>0?$prod['Pack_Size']:1;
        $oProd->save();
        unset($oProd);
    }
}

#funciones
function buscarDatosFactura()
{
    $query = "SELECT * FROM Resolucion WHERE Modulo = 'NoObligados' AND Fecha_Fin > CURDATE() AND Consecutivo <=Numero_Final ORDER BY Fecha_Fin LIMIT 1";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $resolucion = $oCon->getData();
    unset($oCon);

    if ($resolucion['Id_Resolucion']) {
        $oItem = new complex('Resolucion', 'Id_Resolucion', $resolucion['Id_Resolucion']);
        $nc = $oItem->getData();

        $oItem->Consecutivo = $oItem->Consecutivo + 1;
        $oItem->save();

        unset($oItem);

        $cod = $nc["Codigo"] . $nc["Consecutivo"];

        $datos['Codigo'] = $cod;
        $datos['Id_Resolucion'] = $resolucion['Id_Resolucion'];
        $datos['Tipo_Resolucion'] = $resolucion['Tipo_Resolucion'];
        return $datos;
    } else {

        return false;
    }
}

function getResolucion()
{
    $query = "SELECT *
    FROM Resolucion
    WHERE Modulo ='NoObligados' AND
    Fecha_Fin >= CURDATE() AND
    Consecutivo <=Numero_Final
    ORDER BY Fecha_Fin DESC
    LIMIT 1";

    $oCon = new consulta();
    $oCon->setQuery($query);

    $resolucion = $oCon->getData();
    return $resolucion;
}
function generarConsecutivo($resolucion)
{
    return $resolucion ? getConsecutivo($resolucion) : null;
}
function getConsecutivo($resolucion)
{
    $oItem = new complex('Resolucion', 'Id_Resolucion', $resolucion['Id_Resolucion']);

    $res = $oItem->getData();

    $nuevoConsecutivo = $res['Consecutivo'] + 1;
    $oItem->Consecutivo = $nuevoConsecutivo;
    $oItem->save();
    logGuardarDocumento('Consecutivo generado', [
        'id_resolucion' => $resolucion['Id_Resolucion'],
        'anterior' => $res['Consecutivo'],
        'nuevo' => $nuevoConsecutivo,
    ]);
    return "{$res['Codigo']}{$nuevoConsecutivo}";

}
