<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/class.configuracion.php');
include_once('../../class/class.contabilizar.php');
include_once('../../class/class.mipres.php');
require_once('../../class/class.qr.php');
include_once('../../class/class.facturacion_electronica.php');

// ============================================
// FUNCIÓN DE LOGGING
// ============================================
function logError($mensaje, $contexto = [], $nivel = 'ERROR')
{
    return; // logging desactivado por rendimiento

    $logDir = dirname(__DIR__) . '/../storage/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0777, true);
    }
    $logFile = $logDir . '/factura_errors_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextoStr = !empty($contexto) ? ' | Contexto: ' . json_encode($contexto, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';
    $logMessage = "[{$timestamp}] [{$nivel}] {$mensaje}{$contextoStr}" . PHP_EOL;
    @file_put_contents($logFile, $logMessage, FILE_APPEND);
}

function logInfo($mensaje, $contexto = [])
{
    logError($mensaje, $contexto, 'INFO');
}

// ============================================
// INICIALIZACIÓN DE VARIABLES
// ============================================
$queryObj = new QueryBaseDatos();
$contabilizar = new Contabilizar();

// Inicializar variables de respuesta
$resultado = [
    'titulo' => '',
    'mensaje' => '',
    'tipo' => 'error',
    'Id' => null
];

// Inicializar variables de factura y homologo
$factura = [false, null, null, null];
$homologo = [false, null, null, null];
$datos_fac = [];
$datos_hom = [];
$datos_fact = ['Estado' => '', 'Detalles' => ''];
$datos_hom = ['Estado' => '', 'Detalles' => ''];

// Obtener parámetros del request
$mod = isset($_REQUEST['modulo']) ? $_REQUEST['modulo'] : '';
$datos_dis_raw = isset($_REQUEST['datos']) ? $_REQUEST['datos'] : '';
$encabezadoFactura_raw = isset($_REQUEST['encabezadoFactura']) ? $_REQUEST['encabezadoFactura'] : '';
$encabezadoHomologo_raw = isset($_REQUEST['encabezadoHomologo']) ? $_REQUEST['encabezadoHomologo'] : false;
$productosFactura_raw = isset($_REQUEST['productos']) ? $_REQUEST['productos'] : '';
$productosHomologo_raw = isset($_REQUEST['productos1']) ? $_REQUEST['productos1'] : false;
$identificacion_Funcionario = isset($_REQUEST['Identificacion_Funcionario']) ? $_REQUEST['Identificacion_Funcionario'] : false;
$hom = isset($_REQUEST['switch_hom']) ? $_REQUEST['switch_hom'] : '';
$fact = isset($_REQUEST['switch_fact']) ? $_REQUEST['switch_fact'] : '';
$sector_salud = isset($_REQUEST['sector_salud']) ? filter_var($_REQUEST['sector_salud'], FILTER_VALIDATE_BOOLEAN) : false;
$fecha_inicio_periodo_facturacion = isset($_REQUEST['fecha_inicio_periodo_facturacion']) ? trim($_REQUEST['fecha_inicio_periodo_facturacion']) : '';
$fecha_fin_periodo_facturacion = isset($_REQUEST['fecha_fin_periodo_facturacion']) ? trim($_REQUEST['fecha_fin_periodo_facturacion']) : '';

logError('Error al decodificar JSON', [
    'error' => $e->getMessage(),
    'encabezadoFactura' => substr($encabezadoFactura_raw, 0, 200),
    'productosFactura' => substr($productosFactura_raw, 0, 200),
    'datos_dis' => substr($datos_dis_raw, 0, 200)
]);

exit;

$id_factura_asociada = '';

// Decodificar JSON con validación
try {
    $encabezadoFactura = (array) json_decode($encabezadoFactura_raw, true);
    $productosFactura = (array) json_decode($productosFactura_raw, true);
    $datos_dis = (array) json_decode($datos_dis_raw);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error decodificando JSON: ' . json_last_error_msg());
    }
} catch (Exception $e) {
    logError('Error al decodificar JSON', [
        'error' => $e->getMessage(),
        'encabezadoFactura' => substr($encabezadoFactura_raw, 0, 200),
        'productosFactura' => substr($productosFactura_raw, 0, 200),
        'datos_dis' => substr($datos_dis_raw, 0, 200)
    ]);
    $resultado['titulo'] = "Error";
    $resultado['mensaje'] = "Error al procesar los datos JSON: " . $e->getMessage();
    echo json_encode($resultado);
    exit;
}

// Validar que datos_dis tenga la estructura esperada
if (!is_array($datos_dis) || empty($datos_dis)) {
    logError('datos_dis está vacío o no es un array', ['datos_dis' => $datos_dis]);
    $resultado['titulo'] = "Error";
    $resultado['mensaje'] = "Los datos de dispensación son requeridos";
    echo json_encode($resultado);
    exit;
}

// Procesar homologo
if ($encabezadoHomologo_raw && $productosHomologo_raw) {
    try {
        $encabezadoHomologo = (array) json_decode($encabezadoHomologo_raw, true);
        $productosHomologo = (array) json_decode($productosHomologo_raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error decodificando JSON homologo: ' . json_last_error_msg());
        }
    } catch (Exception $e) {
        logError('Error al decodificar JSON homologo', ['error' => $e->getMessage()]);
        $encabezadoHomologo = [];
        $productosHomologo = [];
    }
} else {
    $encabezadoHomologo = [];
    $productosHomologo = [];
}

// Validar Id_Dispensacion
$idDispensacion = null;
if (isset($encabezadoFactura["Id_Dispensacion"]) && !empty($encabezadoFactura["Id_Dispensacion"])) {
    $idDispensacion = $encabezadoFactura["Id_Dispensacion"];
} elseif (isset($encabezadoHomologo["Id_Dispensacion"]) && !empty($encabezadoHomologo["Id_Dispensacion"])) {
    $idDispensacion = $encabezadoHomologo["Id_Dispensacion"];
} else {
    logError('Id_Dispensacion no encontrado', [
        'encabezadoFactura' => array_keys($encabezadoFactura),
        'encabezadoHomologo' => array_keys($encabezadoHomologo)
    ]);
    $resultado['titulo'] = "Error";
    $resultado['mensaje'] = "Id_Dispensacion es requerido";
    echo json_encode($resultado);
    exit;
}

// Validar dispensación
if (!validarDispensacion($idDispensacion)) {
    $resultado['titulo'] = "Creacion no exitosa";
    $resultado['mensaje'] = "La Dispensacion ya ha sido facturada con anterioridad, por favor verifique";
    $resultado['tipo'] = "error";
    echo json_encode($resultado);
    exit;
}

// Procesar factura
if (count($encabezadoFactura) > 0) {
    if ($fact == 'true') {
        try {
            $factura = guardarFactura($encabezadoFactura, $productosFactura, "Factura", $mod);
            if (!$factura) {
                $resultado['titulo'] = "Error";
                $resultado['mensaje'] = "Actualmente no tiene una resolucion activa por favor revise";
                $resultado['tipo'] = "error";
                echo json_encode($resultado);
                exit;
            }
        } catch (Exception $e) {
            logError('Error al guardar factura', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'idDispensacion' => $idDispensacion
            ]);
            $resultado['titulo'] = "Error";
            $resultado['mensaje'] = "Error al guardar la factura: " . $e->getMessage();
            $resultado['tipo'] = "error";
            echo json_encode($resultado);
            exit;
        }
    }
}

// Procesar homologo
if (count($encabezadoHomologo) > 0) {
    if ($hom == 'true') {
        try {
            $homologo = guardarFactura($encabezadoHomologo, $productosHomologo, "Homologo", $mod);
            if (!$homologo) {
                $resultado['titulo'] = "Error";
                $resultado['mensaje'] = "Actualmente no tiene una resolucion activa por favor revise";
                $resultado['tipo'] = "error";
                echo json_encode($resultado);
                exit;
            }
        } catch (Exception $e) {
            logError('Error al guardar homologo', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'idDispensacion' => $idDispensacion
            ]);
            $resultado['titulo'] = "Error";
            $resultado['mensaje'] = "Error al guardar el homologo: " . $e->getMessage();
            $resultado['tipo'] = "error";
            echo json_encode($resultado);
            exit;
        }
    }
}

if ($sector_salud) {
    if ($fecha_inicio_periodo_facturacion === '' || $fecha_fin_periodo_facturacion === '') {
        $resultado['titulo'] = "Error";
        $resultado['mensaje'] = "Debe ingresar las fechas de inicio y fin del periodo de facturación para Sector Salud.";
        $resultado['tipo'] = "error";
        echo json_encode($resultado);
        exit;
    }
    if ($fecha_inicio_periodo_facturacion > $fecha_fin_periodo_facturacion) {
        $resultado['titulo'] = "Error";
        $resultado['mensaje'] = "La fecha de inicio del periodo no puede ser posterior a la fecha fin.";
        $resultado['tipo'] = "error";
        echo json_encode($resultado);
        exit;
    }
}

$sector_salud_config = [
    'activo' => $sector_salud,
    'fecha_inicio' => $fecha_inicio_periodo_facturacion,
    'fecha_fin' => $fecha_fin_periodo_facturacion
];

$tipo_factura_dian = $mod !== '' ? $mod : 'Factura';

// Generar factura electrónica para factura
if (isset($factura[0]) && $factura[0] !== false && isset($factura[2]) && $factura[2] == "Resolucion_Electronica") {
    try {
        $fe1 = new FacturaElectronica($tipo_factura_dian, $factura[1], $factura[3], $sector_salud_config);
        $datos_fac = $fe1->GenerarFactura();
        if (!empty($datos_fac['Datos']['Cufe'])) {
            actualizarFacturaElectronica($tipo_factura_dian, $factura[1], $datos_fac);
        }
    } catch (Exception $e) {
        logError('Error al generar factura electrónica', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'id_factura' => $factura[1]
        ]);
        $datos_fac = ['Estado' => 'Error', 'Detalles' => $e->getMessage()];
    }
}

// Generar factura electrónica para homologo
if (isset($homologo[0]) && $homologo[0] !== false && isset($homologo[2]) && $homologo[2] == "Resolucion_Electronica") {
    try {
        $fe2 = new FacturaElectronica($tipo_factura_dian, $homologo[1], $homologo[3]);
        $datos_hom = $fe2->GenerarFactura();
        if (!empty($datos_hom['Datos']['Cufe'])) {
            actualizarFacturaElectronica($tipo_factura_dian, $homologo[1], $datos_hom);
        }
    } catch (Exception $e) {
        logError('Error al generar homologo electrónico', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'id_homologo' => $homologo[1]
        ]);
        $datos_hom = ['Estado' => 'Error', 'Detalles' => $e->getMessage()];
    }
}

// Determinar resultado final
if (isset($factura[0]) && $factura[0] !== false && isset($homologo[0]) && $homologo[0] !== false) {
    // Ambos factura y homologo
    try {
        if (!isset($encabezadoFactura['Id_Dispensacion'])) {
            throw new Exception('Id_Dispensacion no encontrado en encabezadoFactura');
        }

        $oItem = new complex("Dispensacion", "Id_Dispensacion", $encabezadoFactura['Id_Dispensacion']);
        $dispensacion = $oItem->getData();
        $oItem->Id_Factura = $factura[1];
        $oItem->Fecha_Facturado = date('Y-m-d H:i:s');
        $oItem->Estado_Facturacion = "Facturada";
        $oItem->save();
        unset($oItem);

        $resultado['titulo'] = "Creacion exitosa";
        $resultado['mensaje'] = "Se ha guardado correctamente la Factura con codigo: " . $factura[0] . " Y la Homologacion con codigo: " . $homologo[0] . "\n " .
            (isset($datos_fact["Detalles"]) ? $datos_fact["Detalles"] : '') . " - " .
            (isset($datos_hom["Detalles"]) ? $datos_hom["Detalles"] : '');
        $resultado['tipo'] = "success";
        $resultado['Id'] = $factura[1];
        $resultado['Fact'] = 'Homologo';
        $resultado['Factura'] = $datos_fac;
        $resultado['Homologo'] = $datos_hom;
    } catch (Exception $e) {
        logError('Error al actualizar dispensación (factura + homologo)', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        $resultado['titulo'] = "Error";
        $resultado['mensaje'] = "Error al actualizar la dispensación: " . $e->getMessage();
    }
} elseif (isset($factura[0]) && $factura[0] !== false) {
    // Solo factura
    try {
        if (!isset($encabezadoFactura['Id_Dispensacion'])) {
            throw new Exception('Id_Dispensacion no encontrado en encabezadoFactura');
        }

        $oItem = new complex("Dispensacion", "Id_Dispensacion", $encabezadoFactura['Id_Dispensacion']);
        $dispensacion = $oItem->getData();
        $oItem->Id_Factura = $factura[1];
        $oItem->Fecha_Facturado = date('Y-m-d H:i:s');
        $oItem->Estado_Facturacion = "Facturada";
        $oItem->save();
        unset($oItem);

        $resultado['titulo'] = "Creacion exitosa";
        $resultado['mensaje'] = "Se ha guardado correctamente la Factura con codigo: " . $factura[0] . "\n " .
            (isset($datos_fact["Detalles"]) ? $datos_fact["Detalles"] : '');
        $resultado['tipo'] = "success";
        $resultado['Id'] = $factura[1];
        $resultado['Factura'] = $datos_fac;
    } catch (Exception $e) {
        logError('Error al actualizar dispensación (solo factura)', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        $resultado['titulo'] = "Error";
        $resultado['mensaje'] = "Error al actualizar la dispensación: " . $e->getMessage();
    }
} elseif (isset($homologo[0]) && $homologo[0] !== false) {
    // Solo homologo
    try {
        if (!isset($encabezadoHomologo['Id_Dispensacion'])) {
            throw new Exception('Id_Dispensacion no encontrado en encabezadoHomologo');
        }

        $oItem = new complex("Dispensacion", "Id_Dispensacion", $encabezadoHomologo['Id_Dispensacion']);
        $dispensacion = $oItem->getData();
        $oItem->Id_Factura = $homologo[1];
        $oItem->Fecha_Facturado = date('Y-m-d H:i:s');
        $oItem->Estado_Facturacion = "Facturada";
        $oItem->save();
        unset($oItem);

        $resultado['titulo'] = "Creacion exitosa";
        $resultado['mensaje'] = "Se ha guardado correctamente la Homologación con codigo: " . $homologo[0] . "\n " .
            (isset($datos_hom["Detalles"]) ? $datos_hom["Detalles"] : '');
        $resultado['tipo'] = "success";
        $resultado['Id'] = $homologo[1];
        $resultado['Homologo'] = $datos_hom;
    } catch (Exception $e) {
        logError('Error al actualizar dispensación (solo homologo)', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        $resultado['titulo'] = "Error";
        $resultado['mensaje'] = "Error al actualizar la dispensación: " . $e->getMessage();
    }
} else {
    $resultado['titulo'] = "Creacion no exitosa";
    $resultado['mensaje'] = "No se pudo crear ni la factura ni el homologo";
    $resultado['tipo'] = "error";
}

echo json_encode($resultado);

// ============================================
// FUNCIONES
// ============================================

function guardarFactura($datos, $productos, $tipo, $mod)
{
    global $datos_dis;
    global $id_factura_asociada;
    global $contabilizar;

    try {
        // Validar datos_dis
        if (!is_array($datos_dis) || !isset($datos_dis["Id_Tipo_Servicio"])) {
            throw new Exception("datos_dis no tiene Id_Tipo_Servicio");
        }

        // Validar que datos tenga Id_Regimen si es Factura
        if ($tipo == 'Factura' && isset($datos_dis['Id_Regimen'])) {
            logError('Id_Regimen no encontrado en datos de factura', ['datos_keys' => array_keys($datos)]);
            // Intentar obtener de otra fuente o usar valor por defecto
            $datos['Id_Regimen'] = $datos_dis['Id_Regimen'];
        }

        // Validar que datos tenga Id_Regimen si es Factura
        if ($tipo == 'Factura' && !isset($datos_dis['Id_Regimen'])) {
            logError('Id_Regimen no encontrado en datos de factura', ['datos_keys' => array_keys($datos)]);
            // Intentar obtener de otra fuente o usar valor por defecto
            $datos['Id_Regimen'] = null;
        }

        switch ($tipo) {
            case "Factura": {
                    if ($datos_dis["Id_Tipo_Servicio"] == 9) {
                        $query = "SELECT * FROM Resolucion WHERE Modulo='Evento' AND Consecutivo <=Numero_Final AND Estado = 'Activo' AND Fecha_Fin>=CURDATE() ORDER BY Fecha_Fin ASC LIMIT 1";
                    } elseif ($datos_dis["Id_Tipo_Servicio"] == 21) {
                        $query = "SELECT * FROM Resolucion WHERE Modulo='FARMACIA' AND Consecutivo <=Numero_Final AND Estado = 'Activo' AND Fecha_Fin>=CURDATE() ORDER BY Fecha_Fin ASC LIMIT 1";
                    } else {
                        $query = "SELECT * FROM Resolucion WHERE Modulo='NoPos' AND Consecutivo <=Numero_Final AND Estado = 'Activo' AND Fecha_Fin>=CURDATE() ORDER BY Fecha_Fin ASC LIMIT 1";
                    }
                    break;
                }
            case "Homologo": {
                    if ($datos_dis["Id_Tipo_Servicio"] == 21) {
                        $query = "SELECT * FROM Resolucion WHERE Modulo='FARMACIA' AND Consecutivo <=Numero_Final AND Estado = 'Activo' AND Fecha_Fin>=CURDATE() ORDER BY Fecha_Fin ASC LIMIT 1";
                    } else {
                        $query = "SELECT * FROM Resolucion WHERE Modulo='NoPos' AND Consecutivo <=Numero_Final AND Estado = 'Activo' AND Fecha_Fin>=CURDATE() ORDER BY Fecha_Fin ASC LIMIT 1";
                    }
                    break;
                }
            default: {
                    throw new Exception("Tipo de factura no válido: " . $tipo);
                }
        }

        $oCon = new consulta();
        $oCon->setQuery($query);
        $resolucion = $oCon->getData();
        unset($oCon);

        if (!isset($resolucion['Id_Resolucion']) || empty($resolucion['Id_Resolucion'])) {
            logError('No se encontró resolución activa', [
                'tipo' => $tipo,
                'Id_Tipo_Servicio' => $datos_dis["Id_Tipo_Servicio"]
            ]);
            return false;
        }

        $oItem = new complex('Resolucion', 'Id_Resolucion', $resolucion['Id_Resolucion']);
        $nc = $oItem->getData();
        unset($oItem);

        $cod = getConsecutivo($nc);
        $datos['Codigo'] = $cod;
        $datos['Id_Resolucion'] = $resolucion['Id_Resolucion'];
        $datos["Tipo_Resolucion"] = isset($resolucion["Tipo_Resolucion"]) ? $resolucion["Tipo_Resolucion"] : '';

        $oItem = new complex($mod, "Id_" . $mod);

        foreach ($datos as $index => $value) {
            $oItem->$index = $value;
        }

        if ($tipo == 'Homologo') {
            $id = (int) $id_factura_asociada;
            $oItem->Id_Factura_Asociada = number_format($id, 0, "", "");
        }

        $oItem->save();
        $id_factura = $oItem->getId();

        if ($tipo == 'Factura') {
            $id_factura_asociada = $id_factura;
        }

        unset($oItem);

        // Generar QR
        $oItem = new complex("Factura", "Id_Factura", $id_factura);
        $oItem->Codigo_Qr = "dfgdgf";
        $oItem->save();
        unset($oItem);

        // Procesar productos
        if (is_array($productos) && count($productos) > 0) {
            unset($productos[count($productos) - 1]); // Eliminar última posición

            foreach ($productos as $producto) {
                if (!is_array($producto)) {
                    continue;
                }

                $oItem = new complex('Producto_' . $mod, "Id_Producto_" . $mod);
                $producto["Id_" . $mod] = $id_factura;

                $subtotal = isset($producto['Subtotal']) ? number_format((int) $producto['Subtotal'], 2, ".", "") : 0;
                $producto['Subtotal'] = $subtotal;

                foreach ($producto as $index => $value) {
                    $oItem->$index = $value;
                }

                $impuesto = isset($producto['Impuesto']) && $producto['Impuesto'] != 0 ? (float) $producto['Impuesto'] * 100 : 0;
                $oItem->Impuesto = number_format((int) $impuesto, 0, "", "");
                $oItem->Precio = isset($producto['Precio']) ? number_format($producto['Precio'], 2, ".", "") : 0;
                $oItem->Descuento = isset($producto['Descuento']) ? number_format($producto['Descuento'], 2, ".", "") : 0;
                $oItem->save();
                unset($oItem);
            }
        }

        // Contabilizar si es Factura
        if ($tipo == 'Factura') {
            if (!isset($datos_dis['Id_Punto_Dispensacion'])) {
                logError('Id_Punto_Dispensacion no encontrado en datos_dis', ['datos_dis_keys' => array_keys($datos_dis)]);
            }

            $datos_movimiento_contable = [
                'Id_Registro' => $id_factura,
                'Nit' => isset($datos['Id_Cliente']) ? $datos['Id_Cliente'] : null,
                'Id_Regimen' => isset($datos['Id_Regimen']) ? $datos['Id_Regimen'] : null,
                'Id_Punto_Dispensacion' => isset($datos_dis['Id_Punto_Dispensacion']) ? $datos_dis['Id_Punto_Dispensacion'] : null
            ];

            try {
                $contabilizar->CrearMovimientoContable('Factura', $datos_movimiento_contable);
                ValidarDispensacionMipres($id_factura);
            } catch (Exception $e) {
                logError('Error al crear movimiento contable', [
                    'error' => $e->getMessage(),
                    'id_factura' => $id_factura
                ]);
            }
        }

        if ($id_factura != "" && $id_factura != NULL) {
            return [$cod, $id_factura, $datos["Tipo_Resolucion"], $datos["Id_Resolucion"]];
        } else {
            return false;
        }
    } catch (Exception $e) {
        logError('Error en guardarFactura', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'tipo' => $tipo
        ]);
        throw $e;
    }
}

function actualizarFacturaElectronica($tipo, $id_factura, $datos)
{
    if (!$tipo || !$id_factura) {
        return;
    }

    try {
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
    } catch (Exception $e) {
        logError('Error al actualizar factura electrónica', [
            'error' => $e->getMessage(),
            'tipo' => $tipo,
            'id_factura' => $id_factura
        ]);
    }
}

function ValidarDispensacionMipres($idFactura)
{
    global $mipres;

    try {
        $query = "SELECT * FROM Factura WHERE Id_Factura=$idFactura";

        $oCon = new consulta();
        $oCon->setQuery($query);
        $factura = $oCon->getData();
        unset($oCon);

        if (!isset($factura['Id_Dispensacion'])) {
            logError('Id_Dispensacion no encontrado en factura', ['idFactura' => $idFactura]);
            return;
        }

        $mipres = new Mipres();
        $query = "SELECT Id_Dispensacion_Mipres, Id_Dispensacion FROM Dispensacion WHERE Id_Dispensacion=" . $factura['Id_Dispensacion'];

        $oCon = new consulta();
        $oCon->setQuery($query);
        $dispensacion = $oCon->getData();
        unset($oCon);

        if (!isset($dispensacion['Id_Dispensacion_Mipres'])) {
            logInfo('Dispensación no tiene Id_Dispensacion_Mipres', ['idDispensacion' => $factura['Id_Dispensacion']]);
            return;
        }

        $productos_mipres = GetProductosMipres($dispensacion['Id_Dispensacion_Mipres']);

        if (!is_array($productos_mipres)) {
            logError('GetProductosMipres no devolvió un array', ['idMipres' => $dispensacion['Id_Dispensacion_Mipres']]);
            return;
        }

        foreach ($productos_mipres as $p) {
            if (!isset($p['ID']) || !isset($p['Id_Producto_Dispensacion_Mipres'])) {
                continue;
            }

            $subtotalProducto = GetSubtotalProducto($p['Id_Producto'], $idFactura, $dispensacion['Id_Dispensacion_Mipres']);
            $data = [
                'ID' => (int) $p['ID'],
                'EstadoEntrega' => 1,
                'CausaNoEntrega' => 0,
                'ValorEntregado' => $subtotalProducto
            ];

            try {
                $respuesta = $mipres->ReportarEntregaEfectiva($data);

                if (
                    isset($respuesta) && is_array($respuesta) && isset($respuesta[0]) &&
                    isset($respuesta[0]['Id']) && $respuesta[0]['Id']
                ) {
                    $oItem = new complex('Producto_Dispensacion_Mipres', 'Id_Producto_Dispensacion_Mipres', $p['Id_Producto_Dispensacion_Mipres']);
                    if (isset($respuesta[0]['IdReporteEntrega'])) {
                        $oItem->IdReporteEntrega = $respuesta[0]['IdReporteEntrega'];
                    }
                    $oItem->save();
                    unset($oItem);
                }
            } catch (Exception $e) {
                logError('Error al reportar entrega efectiva Mipres', [
                    'error' => $e->getMessage(),
                    'idProducto' => $p['ID'] ?? 'N/A'
                ]);
            }
        }
    } catch (Exception $e) {
        logError('Error en ValidarDispensacionMipres', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'idFactura' => $idFactura
        ]);
    }
}

function GetProductosMipres($idMipres)
{
    try {
        $query = "SELECT * FROM Producto_Dispensacion_Mipres WHERE Id_Dispensacion_Mipres=$idMipres";

        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $productos = $oCon->getData();
        unset($oCon);

        return is_array($productos) ? $productos : [];
    } catch (Exception $e) {
        logError('Error en GetProductosMipres', [
            'error' => $e->getMessage(),
            'idMipres' => $idMipres
        ]);
        return [];
    }
}

function GetSubtotalProducto($idProducto, $idFactura, $id_mipres)
{
    try {
        $query = "SELECT PD.Id_Producto FROM Dispensacion D INNER JOIN Producto_Dispensacion PD ON D.Id_Dispensacion=PD.Id_Dispensacion WHERE D.Id_Dispensacion_Mipres=$id_mipres AND PD.Id_Producto_Mipres= $idProducto";

        $oCon = new consulta();
        $oCon->setQuery($query);
        $prod = $oCon->getData();
        unset($oCon);

        if (isset($prod['Id_Producto']) && $prod['Id_Producto'] != '') {
            $query = "SELECT SUM(Cantidad*Precio) as Subtotal FROM Producto_Factura WHERE Id_Factura=$idFactura AND Id_Producto=" . $prod['Id_Producto'];

            $oCon = new consulta();
            $oCon->setQuery($query);
            $subt = $oCon->getData();
            unset($oCon);

            return isset($subt['Subtotal']) ? (float) $subt['Subtotal'] : 0;
        } else {
            return 0;
        }
    } catch (Exception $e) {
        logError('Error en GetSubtotalProducto', [
            'error' => $e->getMessage(),
            'idProducto' => $idProducto,
            'idFactura' => $idFactura
        ]);
        return 0;
    }
}

function getConsecutivo($resolucion)
{
    try {
        if (!isset($resolucion['Codigo']) || !isset($resolucion['Consecutivo']) || !isset($resolucion['Id_Resolucion'])) {
            throw new Exception('Resolución no tiene los campos requeridos');
        }

        $cod = $resolucion['Codigo'] != '0' ? $resolucion['Codigo'] . $resolucion['Consecutivo'] : $resolucion['Consecutivo'];
        $oItem = new complex('Resolucion', 'Id_Resolucion', $resolucion['Id_Resolucion']);
        $new_cod = $oItem->Consecutivo + 1;
        $oItem->Consecutivo = number_format($new_cod, 0, "", "");
        $oItem->save();
        unset($oItem);

        sleep(strval(rand(1, 110)));

        $query = "SELECT Id_Factura FROM Factura WHERE Codigo = '$cod'";
        $oCon = new consulta();
        $oCon->setQuery($query);
        $res = $oCon->getData();
        unset($oCon);

        if (isset($res) && is_array($res) && isset($res["Id_Factura"]) && $res["Id_Factura"]) {
            $oItem = new complex('Resolucion', 'Id_Resolucion', $resolucion['Id_Resolucion']);
            $nc = $oItem->getData();
            unset($oItem);
            sleep(strval(rand(0, 3)));
            return getConsecutivo($nc);
        }

        return $cod;
    } catch (Exception $e) {
        logError('Error en getConsecutivo', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        throw $e;
    }
}

function validarDispensacion($id)
{
    if (empty($id)) {
        logError('validarDispensacion recibió ID vacío');
        return false;
    }

    try {
        $query = "SELECT Id_Factura, Fecha_Documento FROM Factura WHERE Estado_Factura !='Anulada' AND Nota_Credito != 'Si' AND Id_Dispensacion = " . (int)$id;

        $oCon = new consulta();
        $oCon->setQuery($query);
        $res = $oCon->getData();
        unset($oCon);

        if (isset($res) && is_array($res) && isset($res["Id_Factura"]) && $res["Id_Factura"]) {
            $oItem = new complex("Dispensacion", "Id_Dispensacion", $id);
            $oItem->Id_Factura = $res["Id_Factura"];
            $oItem->Fecha_Facturado = isset($res["Fecha_Documento"]) ? $res["Fecha_Documento"] : date('Y-m-d H:i:s');
            $oItem->Estado_Facturacion = "Facturada";
            $oItem->save();
            unset($oItem);

            return false;
        } else {
            return true;
        }
    } catch (Exception $e) {
        logError('Error en validarDispensacion', [
            'error' => $e->getMessage(),
            'id' => $id
        ]);
        return false;
    }
}
