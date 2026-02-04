<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-type:application/json');

require_once '../../config/start.inc.php';
include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';
include_once __DIR__ . '/services/ReportCache.php';
include_once __DIR__ . '/services/VentasReportService.php';

if (isset($_REQUEST['tipo']) && $_REQUEST['tipo'] != "") {
    $tipo = $_REQUEST['tipo'];
    $query = CrearQuery($tipo);
    ArmarReporte($query);
}

function ArmarReporte($query)
{

    global $tipo;
    $debug = !empty($_REQUEST['debug']);
    error_reporting(E_ALL);
    ini_set('display_errors', $debug ? '1' : '0'); // En debug se muestran errores, en producción se ocultan para no romper headers.

    if (function_exists('ob_get_length') && ob_get_length()) {
        error_log('Salida detectada antes de preparar el XLS (longitud ' . ob_get_length() . ')');
        if ($debug) {
            ob_clean(); // Limpia cualquier salida previa que pueda dañar los headers.
        } else {
            ob_clean();
        }
    }

    $validar_codigos = ValidarCodigos($tipo);
    //Encabezado según tipo y consulta normalizada (evita columnas desalineadas).
    $encabezado = GetEncabezado($tipo, $query);
    $valores_encabezado = $encabezado ? array_keys($encabezado) : []; // Nombres de columnas para el Excel; si falla, usar arreglo vacío.


    /*  echo '<pre>';
    var_dump($datos);exit;
    echo '</pre>'; */
    $contenido = '';
    $contenido1 = '';

    // Carpeta preferida dentro del repositorio; si no es escribible, se usa /tmp/tablerocontabilidad.
    $carpeta = __DIR__ . "/archivo";
    if (!is_dir($carpeta)) {
        if (!mkdir($carpeta, 0777, true)) {
            error_log("No se pudo crear la carpeta de reportes: $carpeta");
        }
    }
    if (!is_dir($carpeta) || !is_writable($carpeta)) {
        $tmpFallback = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . '/tablerocontabilidad';
        if (!is_dir($tmpFallback) && !mkdir($tmpFallback, 0777, true)) {
            error_log("No se pudo preparar la carpeta temporal de reportes: $tmpFallback");
            if ($debug) {
                echo "No se pudo preparar la carpeta temporal de reportes: $tmpFallback";
            }
            return;
        }
        $carpeta = $tmpFallback;
        @chmod($carpeta, 0777);
        error_log("Usando carpeta temporal para reportes: $carpeta");
    }

    $id = "Reporte_" . $tipo . uniqid();
    $archivo = $carpeta . '/' . $id . '.xls';
    if (file_exists($archivo) && !is_writable($archivo)) {
        error_log("El archivo no es sobrescribible: $archivo");
        if ($debug) {
            echo "El archivo no es sobrescribible: $archivo";
        }
        return;
    }
    if (file_exists($archivo) && !@unlink($archivo)) {
        error_log("No se pudo eliminar archivo previo: $archivo");
    }

    $handle = @fopen($archivo, 'w+');
    if ($handle === false) {
        error_log("No se pudo abrir el archivo para escritura: $archivo");
        if ($debug) {
            echo "No se pudo abrir el archivo para escritura: $archivo";
        }
        return;
    }
    if ($encabezado) {
        //Se eliminó la asignación de $valores_encabezado aquí porque ahora se calcula antes del render (evita duplicar lógica)
        $contenido .= '<table border="1"><tr>';
        foreach ($encabezado as $key => $value) {
            $key = str_replace('_', ' ', $key);
            $contenido .= '<th>' . $key . '</th>';
        }
        $contenido .= '</tr>';
    }
    fwrite($handle, $contenido);

    $cache = null;
    $ventasService = null;
    $ventasCachePlan = null;
    $ventasRange = null;
    if ($tipo === 'Ventas') {
        $cacheDir = dirname(__DIR__, 2) . '/storage/reportes_cache';
        $cache = new ReportCache($cacheDir, 300);
        $ventasService = new VentasReportService();
        $ventasRange = [
            'inicio' => isset($_REQUEST['fini']) ? $_REQUEST['fini'] : '',
            'fin' => isset($_REQUEST['ffin']) ? $_REQUEST['ffin'] : ''
        ];
        $condicion_nit_ventas = '';
        if (isset($_REQUEST['nit']) && $_REQUEST['nit'] != '') {
            $condicion_nit_ventas = " AND F.Id_Cliente=" . $_REQUEST['nit'];
        }
        $ventasCachePlan = buildVentasCachePlan($ventasService, $ventasRange, $_REQUEST, $condicion_nit_ventas);
    }

    $errores_estructura = []; //Se inicializa arreglo para capturar errores de estructura del reporte. 
    if (true) {
        foreach ($query as $indiceQuery => $q) { // Se agrega $indiceQuery para identificar de qué consulta proviene cada registro.
            $datos = null;
            if ($cache && $ventasCachePlan) {
                $datos = getVentasCachedData(
                    $cache,
                    $ventasService,
                    $ventasCachePlan,
                    $ventasRange,
                    $indiceQuery
                );
            } else {
                $datos = GetDatos($q);
            }
            if ($datos === false || $datos === null) {
                error_log("GetDatos() retornó sin datos (false/null) para la consulta {$indiceQuery}");
                if ($debug) {
                    echo "GetDatos() retornó sin datos (false/null) para la consulta {$indiceQuery}";
                }
                continue;
            }
            if (!is_array($datos) || empty($datos)) {
                error_log("GetDatos() retornó vacío para la consulta {$indiceQuery}");
                continue;
            }
            foreach ($datos as $i => $dato) {
                $contenido = '';
                //VALOR NUMERICO DEL CODIGO
                if ($i != 0 && $validar_codigos) {
                    $numero_actual = preg_replace('/[^0-9]/', "", $dato['Codigo']);
                    //PREFIJO DEL CODIGO
                    $prefijo_cod = str_replace($numero_actual, "", $dato['Codigo']);

                    $numero_anterior = preg_replace('/[^0-9]/', "", $datos[$i - 1]['Codigo']);
                    $prefijo_anterior_cod = str_replace($numero_anterior, "", $datos[$i - 1]['Codigo']);

                    if ($prefijo_anterior_cod == $prefijo_cod) {
                        # code...
                        //VALIDAR SI EXISTEN CONSECUTIVOS SALTADO
                        while (($numero_actual - $numero_anterior > 1)) {
                            $numero_anterior++;
                            $contenido .= '<tr style="background-color:red">';
                            $contenido .= '<td>' . $prefijo_cod . $numero_anterior . '</td>';
                            for ($col = 1; $col < count($valores_encabezado); $col++) {
                                # code...
                                if (ValidarKey($valores_encabezado[$col])) {
                                    $contenido .= '<td> 0 </td>';
                                } else {

                                    if ($valores_encabezado[$col] == 'Tipo_Servicio') {
                                        $contenido .= '<td> ' . $dato['Tipo_Servicio'] . '  </td>';
                                    } else if ($valores_encabezado[$col] == 'Estado') {
                                        $contenido .= '<td>Anulada</td>';
                                    } else {

                                        $contenido .= '<td>   </td>';
                                    }
                                }
                            }
                            $contenido .= '</tr>';
                        }
                    }
                }

                $contenido .= '<tr>';

                if ($valores_encabezado) { // Validación de estructura según encabezado (detecta columnas faltantes/extras).
                    $keys_dato = array_keys($dato);
                    $faltantes = array_diff($valores_encabezado, $keys_dato);
                    $extras = array_diff($keys_dato, $valores_encabezado);
                    if ($faltantes || $extras) { // Se registra cualquier desalineación por consulta/fila.
                        $errores_estructura[] = [
                            'consulta' => $indiceQuery,
                            'fila' => $i,
                            'faltantes' => $faltantes,
                            'extras' => $extras
                        ];
                    }
                    foreach ($valores_encabezado as $key) {  // Se imprime siguiendo el orden del encabezado unificado.
                        $valorCelda = array_key_exists($key, $dato) ? $dato[$key] : '';
                        if (ValidarKey($key)) {
                            $valor = $valorCelda !== '' ? $valorCelda : 0;
                            try {
                                $valor = (float) $valor;
                                $contenido .= '<td>' . number_format($valor, 2, ",", "") . '</td>';
                            } catch (\Throwable $th) {
                                error_log("Error formateando valor numerico en columna {$key} para tipo {$tipo}: " . $th->getMessage());
                                continue;
                            }
                        } else {
                            $contenido .= '<td>' . $valorCelda . '</td>';
                        }
                    }
                } else {
                    foreach ($dato as $key => $value) {  // Si no hay encabezado unificado, se imprime según el orden original (puede desalinear columnas).

                        if (ValidarKey($key)) {
                            $valor = $dato[$key] != '' ? $dato[$key] : 0;
                            try {
                                //code...
                                $valor = (float) $valor;
                                $contenido .= '<td>' . number_format($valor, 2, ",", "") . '</td>';
                            } catch (\Throwable $th) {
                                //throw $th;
                                error_log("Error formateando valor numerico en columna {$key} para tipo {$tipo}: " . $th->getMessage());
                                continue;
                            }
                        } else {
                            $contenido .= '<td>' . $dato[$key] . '</td>';
                        }
                        // fwrite($handle, $contenido);
                    }
                }

                $contenido .= '</tr>';
                fwrite($handle, $contenido);
            }
        }
        /*    exit; */
        $contenido = '</table>';
        fwrite($handle, $contenido);
    }

    if (!empty($errores_estructura)) { // Log de inconsistencias detectadas en la estructura del reporte.
        foreach ($errores_estructura as $error) {
            error_log('Reporte ' . $tipo . ' estructura invalida (consulta ' . $error['consulta'] . ', fila ' . $error['fila'] . ') faltantes: ' . implode(',', $error['faltantes']) . ' extras: ' . implode(',', $error['extras']));
        }
    }

    if ($contenido == '') {
        $contenido .= '
        <table>
        <tr>
        <td>NO EXISTE INFORMACION PARA MOSTRAR</td>
        </tr>
        </table>
        ';
        fwrite($handle, $contenido);
    }
    fflush($handle);
    fclose($handle);

    clearstatcache(true, $archivo);
    if (!file_exists($archivo)) {
        error_log("El archivo a descargar no existe: $archivo");
        if ($debug) {
            echo "El archivo a descargar no existe: $archivo";
        }
        return;
    }

    if (!is_readable($archivo)) {
        error_log("El archivo no es legible: $archivo");
        if ($debug) {
            echo "El archivo no es legible: $archivo";
        }
        return;
    }

    if (headers_sent($file, $line)) {
        error_log("Los headers ya fueron enviados en {$file}:{$line}");
    }

    if (function_exists('header_remove')) {
        header_remove('Content-Type'); // Asegura que el header JSON inicial no dañe la descarga.
    }

    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="Reporte_' . $_REQUEST['tipo'] . '.xls"');
    header('Cache-Control: max-age=0');
    $bytes = readfile($archivo);
    if ($bytes === false) {
        error_log("Falló la lectura del archivo: $archivo");
    }

    // sleep(10);
    // unlink($archivo);


}
function deleteDirectory($dir)
{
    if (!$dh = @opendir($dir)) {
        return;
    }

    while (false !== ($current = readdir($dh))) {
        if ($current != '.' && $current != '..') {
            if (!@unlink($dir . '/' . $current)) {
                deleteDirectory($dir . '/' . $current);
            }
        }
    }
    closedir($dh);
    @rmdir($dir);
}
function GetEncabezado($tipo, $query)
{
    $columnModel = GetColumnModel($tipo); // Si existe modelo estándar para este tipo, usarlo como encabezado.
    if (!empty($columnModel)) {
        return array_fill_keys($columnModel, null);
    }  // Si no hay consultas válidas, regresar encabezado vacío.
    if (empty($query) || !isset($query[0]) || trim($query[0]) === '') {
        return [];
    }

    $oCon = new consulta();
    $oCon->setQuery($query[0]);
    $encabezado = $oCon->getData();
    unset($oCon);

    return $encabezado;
}

function GetColumnModel($tipo)
{ // Modelo de columnas unificado por tipo de reporte (controla estructura fija).
    $modelos = [
        'Ventas' => [
            'Factura',
            'Codigo',
            'Id_Resolucion',
            'Fecha_Factura',
            'NIT_Cliente',
            'Nombre_Cliente',
            'Zona_Comercial',
            'Gravada',
            'Excenta',
            'Iva',
            'Descuentos_Gravados',
            'Descuentos_Excentos',
            'Cuota_Moderadora',
            'Total_Venta',
            'Neto_Factura',
            'Costo_Venta_Exenta',
            'Costo_Venta_Gravada',
            'Total_Costo_Venta',
            'Rentabilidad',
            'Estado',
            'Tipo_Servicio',
            'Punto',
            'Ciudad',
            'Prefijo',
            'Movimiento_Contable'
        ]
    ];
    // Retorna el modelo si existe; si no, un arreglo vacío
    return isset($modelos[$tipo]) ? $modelos[$tipo] : [];
}

function GetDatos($query)
{
    
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $datos = $oCon->getData();
    unset($oCon);
    return $datos;
}

function buildVentasCachePlan($ventasService, $ventasRange, $request, $condicion_nit)
{
    $inicio = isset($ventasRange['inicio']) ? $ventasRange['inicio'] : '';
    $fin = isset($ventasRange['fin']) ? $ventasRange['fin'] : '';
    if ($inicio === '' || $fin === '' || $inicio === 'undefined' || $fin === 'undefined') {
        return null;
    }
    $startTs = strtotime($inicio);
    $endTs = strtotime($fin);
    if ($startTs === false || $endTs === false) {
        return null;
    }

    $months = [];
    $current = new DateTime(date('Y-m-01', $startTs));
    $endMonth = new DateTime(date('Y-m-01', $endTs));
    while ($current <= $endMonth) {
        $monthStart = $current->format('Y-m-01');
        $monthEnd = $current->format('Y-m-t');
        $monthRequest = [
            'fini' => $monthStart,
            'ffin' => $monthEnd
        ];
        if (isset($request['nit']) && $request['nit'] !== '') {
            $monthRequest['nit'] = $request['nit'];
        }
        $months[] = [
            'start' => $monthStart,
            'end' => $monthEnd,
            'prefix' => $ventasService->getCachePrefix($monthRequest),
            'ttl' => $ventasService->getCacheTtlSeconds($monthRequest),
            'queries' => $ventasService->buildQueries($monthRequest, $condicion_nit)
        ];
        $current->modify('first day of next month');
    }

    return [
        'months' => $months
    ];
}

function getVentasCachedData($cache, $ventasService, $ventasCachePlan, $ventasRange, $indiceQuery)
{
    $inicio = isset($ventasRange['inicio']) ? $ventasRange['inicio'] : '';
    $fin = isset($ventasRange['fin']) ? $ventasRange['fin'] : '';
    $startTs = strtotime($inicio . ' 00:00:00');
    $endTs = strtotime($fin . ' 23:59:59');
    $hasRange = $startTs !== false && $endTs !== false;

    $merged = [];
    foreach ($ventasCachePlan['months'] as $month) {
        $monthQuery = $month['queries'][$indiceQuery];
        $cacheKey = $cache->keyForQuery($month['prefix'] . '_q' . $indiceQuery, $monthQuery);
        $monthData = $cache->get($cacheKey, $month['ttl']);
        if ($monthData === null) {
            $monthData = GetDatos($monthQuery);
            if (is_array($monthData) && !empty($monthData)) {
                $cache->set($cacheKey, $monthData);
            }
        }
        if (is_array($monthData) && !empty($monthData)) {
            $merged = array_merge($merged, $monthData);
        }
    }

    if (!$hasRange || empty($merged)) {
        return $merged;
    }

    $filtrados = [];
    foreach ($merged as $row) {
        if (!isset($row['Fecha_Factura'])) {
            $filtrados[] = $row;
            continue;
        }
        $rowTs = strtotime($row['Fecha_Factura']);
        if ($rowTs === false) {
            $filtrados[] = $row;
            continue;
        }
        if ($rowTs >= $startTs && $rowTs <= $endTs) {
            $filtrados[] = $row;
        }
    }

    return $filtrados;
}
function CrearQuery($tipo)
{

    $condicion_nit = '';
    if ($_REQUEST['nit'] && $_REQUEST['nit'] != '') {
        if ($tipo == 'DevolucionC') {
            $condicion_nit .= " AND NC.Id_Proveedor=$_REQUEST[nit]";
        } elseif ($tipo == 'Acta_Compra') {
            $condicion_nit .= " AND P.Id_Proveedor=$_REQUEST[nit]";
        } elseif ($tipo == 'Reporte_Nacionalizacion') {
            $condicion_nit .= " AND OCI.Id_Proveedor=$_REQUEST[nit]";
        } elseif ($tipo == 'Dispensacion') {
            $condicion_nit .= " HAVING Nit_Cobrar=$_REQUEST[nit]";
        } else {
            $condicion_nit .= " AND F.Id_Cliente=$_REQUEST[nit]";
        }
    }

    switch ($tipo) {
        case 'Inventario_Valorizado':
            $fecha = isset($_REQUEST['fini']) ? $_REQUEST['fini'] : false;
            $fecha = date("Y-m", strtotime($fecha));
            $fecha_emision = "$fecha-01";
            $fecha_emision = strtotime($fecha_emision);
            $fecha_ultima_compra = date("Y,m,d", strtotime("+1 month", $fecha_emision));
            $fecha_ultima_compra = str_replace(',', '-', $fecha_ultima_compra);
            $query = "SELECT
            DATE_FORMAT(IV.Fecha_Documento, '%Y-%m-%d') Fecha_Documento,
            IF(DI.Tipo_Origen = 'Bodega_Nuevo',
                (SELECT
                        Nombre
                    FROM
                        Bodega_Nuevo
                    WHERE
                        Id_Bodega_Nuevo = DI.Id_Origen),
                (SELECT
                        Nombre
                    FROM
                        Punto_Dispensacion
                    WHERE
                        Id_Punto_Dispensacion = DI.Id_Origen)) AS Origen,
            DI.Cantidad,
            DI.Costo_Promedio,
            (DI.Costo_Promedio * DI.Cantidad) AS Valor,
            P.Nombre_Comercial AS Nombre_Producto,
            P.Tipo,
            P.Laboratorio_Comercial,
            P.Codigo_Cum,
            I.Lote,
            I.Fecha_Vencimiento,
            I.Fecha_Carga,
            I.Lista_Ganancia,
            COALESCE((SELECT
                            CT.Nombre
                        FROM
                            Categoria_Nueva CT
                        WHERE
                            CT.Id_Categoria_Nueva = SUB.Id_Categoria_Nueva),
                    ' ') AS Categoria_Nueva,
            COALESCE(SUB.Nombre, ' ') AS Subcategoria,
            COALESCE((SELECT
                            PA.Precio
                        FROM
                            Producto_Acta_Recepcion PA
                                INNER JOIN
                            Acta_Recepcion AR ON AR.Id_Acta_Recepcion = PA.Id_Acta_Recepcion
                        WHERE
                            PA.Id_Producto = P.Id_Producto
                                AND DATE(AR.Fecha_Creacion) < '$fecha_ultima_compra'
                                AND AR.Estado != 'Anulada'
                        ORDER BY PA.Id_Producto_Acta_Recepcion DESC
                        LIMIT 1),
                    (SELECT
                            PA.Precio_Unitario_Pesos
                        FROM
                            Producto_Nacionalizacion_Parcial PA
                                INNER JOIN
                            Nacionalizacion_Parcial AR ON AR.Id_Nacionalizacion_Parcial = PA.Id_Nacionalizacion_Parcial
                        WHERE
                            PA.Id_Producto = P.Id_Producto
                                AND DATE(AR.Fecha_Registro) < '$fecha_ultima_compra'
                                AND AR.Estado != 'Anulado'
                        ORDER BY PA.Id_Producto_Nacionalizacion_Parcial DESC
                        LIMIT 1),
                    0) AS Ultima_Compra
            FROM
                Inventario_Valorizado IV
                    INNER JOIN
                Descripcion_Inventario_Valorizado DI ON DI.Id_Inventario_Valorizado = IV.Id_Inventario_Valorizado
                    INNER JOIN
                Producto P ON P.Id_Producto = DI.Id_Producto
                    INNER JOIN
                Inventario_Nuevo I ON I.Id_Inventario_Nuevo = DI.Id_Inventario_Nuevo
                    LEFT JOIN
                Subcategoria SUB ON SUB.Id_Subcategoria = P.Id_Subcategoria
            WHERE
                IV.Fecha_Documento LIKE '$fecha%'
                    AND IV.Estado = 'Activo'";
            // echo $query; exit;
            break;
        case 'Ventas':
            $ventasService = new VentasReportService();
            $query = $ventasService->buildQueries($_REQUEST, $condicion_nit);
            break;
        case 'DevolucionV':
            $fecha_inicio = '';
            $fecha_fin = '';
            if (isset($_REQUEST['fini']) && $_REQUEST['fini'] != "" && $_REQUEST['fini'] != "undefined") {
                $fecha_inicio = $_REQUEST['fini'];
            }
            if (isset($_REQUEST['ffin']) && $_REQUEST['ffin'] != "" && $_REQUEST['ffin'] != "undefined") {
                $fecha_fin = $_REQUEST['ffin'];
            }

            $condicion_nit = $condicion_nit != '' ? " AND NC.Id_Cliente = $_REQUEST[nit]" : '';

            $query = "SELECT
                        NC.Codigo AS Devolucion_Venta, NC.Codigo,
                        NC.Fecha AS Fecha_Devolucion_Venta,
                        NC.Id_Cliente AS NIT,
                        C.Nombre AS Cliente,

                        (
                                CASE NC.Estado
                                WHEN 'Anulada' THEN 0
                                ELSE
                                IFNULL((SELECT SUM(PDC.Cantidad*PDC.Precio_Venta)

                            FROM Producto_Nota_Credito PDC WHERE PDC.Id_Nota_Credito = NC.Id_Nota_Credito AND PDC.Impuesto!=0),0)
                            END
                        ) as Gravado,
                        (
                                CASE NC.Estado
                                WHEN 'Anulada' THEN 0
                                ELSE
                                IFNULL((SELECT SUM(PDC.Cantidad*PDC.Precio_Venta)
                                FROM Producto_Nota_Credito PDC WHERE PDC.Id_Nota_Credito = NC.Id_Nota_Credito AND PDC.Impuesto=0),0)
                                END
                        ) as Excento,
                        (
                                CASE NC.Estado
                                WHEN 'Anulada' THEN 0
                                ELSE
                                IFNULL((SELECT ROUND(SUM(PDC.Cantidad*PDC.Precio_Venta*(0.19)))
                                FROM Producto_Nota_Credito PDC WHERE PDC.Id_Nota_Credito = NC.Id_Nota_Credito AND PDC.Impuesto!=0),0)
                                END
                        ) AS Iva,

                        (
                                CASE NC.Estado
                                WHEN 'Anulada' THEN 0
                                ELSE
                        IFNULL((SELECT SUM(PDC.Cantidad*PDC.Precio_Venta)
                        FROM Producto_Nota_Credito PDC WHERE PDC.Id_Nota_Credito = NC.Id_Nota_Credito AND PDC.Impuesto!=0),0) + IFNULL((SELECT SUM(PDC.Cantidad*PDC.Precio_Venta)
                        FROM Producto_Nota_Credito PDC WHERE PDC.Id_Nota_Credito = NC.Id_Nota_Credito AND PDC.Impuesto=0),0)
                        END
                        ) AS Total,

                        (
                                CASE NC.Estado
                                WHEN 'Anulada' THEN 0
                                ELSE
                        IFNULL((SELECT SUM(PDC.Cantidad*PDC.Precio_Venta)
                        FROM Producto_Nota_Credito PDC WHERE PDC.Id_Nota_Credito = NC.Id_Nota_Credito AND PDC.Impuesto!=0),0) + IFNULL((SELECT SUM(PDC.Cantidad*PDC.Precio_Venta)
                        FROM Producto_Nota_Credito PDC WHERE PDC.Id_Nota_Credito = NC.Id_Nota_Credito AND PDC.Impuesto=0),0) + IFNULL((SELECT ROUND(SUM(PDC.Cantidad*PDC.Precio_Venta*(0.19)))
                        FROM Producto_Nota_Credito PDC WHERE PDC.Id_Nota_Credito = NC.Id_Nota_Credito AND PDC.Impuesto!=0),0)
                        END
                        ) AS Neto,
                        NC.Estado
                        FROM Nota_Credito NC
                        INNER JOIN Cliente C
                        ON C.Id_Cliente = NC.Id_Cliente
                        WHERE (DATE(NC.Fecha) BETWEEN '$fecha_inicio' AND '$fecha_fin') $condicion_nit
                        AND NC.Procesada = 'True'
                        HAVING Neto != 0
                        ORDER BY CONVERT(SUBSTRING(NC.Codigo, 3),UNSIGNED INTEGER) ";
            break;
        case 'DevolucionC':
            $fecha_inicio = '';
            $fecha_fin = '';
            if (isset($_REQUEST['fini']) && $_REQUEST['fini'] != "" && $_REQUEST['fini'] != "undefined") {
                $fecha_inicio = $_REQUEST['fini'];
            }
            if (isset($_REQUEST['ffin']) && $_REQUEST['ffin'] != "" && $_REQUEST['ffin'] != "undefined") {
                $fecha_fin = $_REQUEST['ffin'];
            }
            $condicion_nit = $condicion_nit != '' ? " AND NC.Id_Proveedor = $_REQUEST[nit]" : '';
            $query = "SELECT
                        NC.Codigo AS Devolucion_Compra, NC.Codigo,
                        NC.Fecha AS Fecha_Devolucion_Compra,
                        NC.Id_Proveedor AS NIT,
                        C.Nombre AS Cliente,
                        (
                                CASE NC.Estado
                                WHEN 'Anulada' THEN 0
                                ELSE
                                    IFNULL((SELECT SUM(PDC.Cantidad*PDC.Costo)
                                    FROM Producto_Devolucion_Compra PDC WHERE PDC.Id_Devolucion_Compra = NC.Id_Devolucion_Compra AND PDC.Impuesto!=0),0)
                                END
                        ) as Gravado,

                        (
                                CASE NC.Estado
                                WHEN 'Anulada' THEN 0
                                ELSE

                            IFNULL((SELECT SUM(PDC.Cantidad*PDC.Costo)
                            FROM Producto_Devolucion_Compra PDC WHERE PDC.Id_Devolucion_Compra = NC.Id_Devolucion_Compra AND PDC.Impuesto=0),0)
                            END
                        ) as Excento,

                        (
                                CASE NC.Estado
                                WHEN 'Anulada' THEN 0
                                ELSE
                            IFNULL((SELECT ROUND(SUM(PDC.Cantidad*PDC.Costo*(0.19)))
                            FROM Producto_Devolucion_Compra PDC WHERE PDC.Id_Devolucion_Compra = NC.Id_Devolucion_Compra AND PDC.Impuesto!=0),0)
                                END
                        ) AS Iva,

                        (
                                CASE NC.Estado
                                WHEN 'Anulada' THEN 0
                                ELSE
                        IFNULL((SELECT SUM(Debe) FROM Movimiento_Contable WHERE Id_Modulo = 16 AND Id_Registro_Modulo = NC.Id_Devolucion_Compra AND Id_Plan_Cuenta = 320),0)
                        END
                        ) AS Rte_Fuente,

                        (
                                CASE NC.Estado
                                WHEN 'Anulada' THEN 0
                                ELSE
                        IFNULL((SELECT SUM(Debe) FROM Movimiento_Contable WHERE Id_Modulo = 16 AND Id_Registro_Modulo = NC.Id_Devolucion_Compra AND Id_Plan_Cuenta = 328),0)
                        END
                        ) AS Rte_Ica,

                        (
                                CASE NC.Estado
                                WHEN 'Anulada' THEN 0
                                ELSE
                        IFNULL((SELECT SUM(PDC.Cantidad*PDC.Costo)
                        FROM Producto_Devolucion_Compra PDC WHERE PDC.Id_Devolucion_Compra = NC.Id_Devolucion_Compra AND PDC.Impuesto!=0),0) + IFNULL((SELECT SUM(PDC.Cantidad*PDC.Costo)
                        FROM Producto_Devolucion_Compra PDC WHERE PDC.Id_Devolucion_Compra = NC.Id_Devolucion_Compra AND PDC.Impuesto=0),0)
                        END
                        ) AS Total,

                        (
                                CASE NC.Estado
                                WHEN 'Anulada' THEN 0
                                ELSE
                        IFNULL((SELECT SUM(PDC.Cantidad*PDC.Costo)
                        FROM Producto_Devolucion_Compra PDC WHERE PDC.Id_Devolucion_Compra = NC.Id_Devolucion_Compra AND PDC.Impuesto!=0),0) + IFNULL((SELECT SUM(PDC.Cantidad*PDC.Costo)
                        FROM Producto_Devolucion_Compra PDC WHERE PDC.Id_Devolucion_Compra = NC.Id_Devolucion_Compra AND PDC.Impuesto=0),0) + IFNULL((SELECT ROUND(SUM(PDC.Cantidad*PDC.Costo*(0.19)))
                        FROM Producto_Devolucion_Compra PDC WHERE PDC.Id_Devolucion_Compra = NC.Id_Devolucion_Compra AND PDC.Impuesto!=0),0) - IFNULL((SELECT SUM(Debe) FROM Movimiento_Contable WHERE Id_Modulo = 16 AND Id_Registro_Modulo = NC.Id_Devolucion_Compra AND Id_Plan_Cuenta = 320),0) - IFNULL((SELECT SUM(Debe) FROM Movimiento_Contable WHERE Id_Modulo = 16 AND Id_Registro_Modulo = NC.Id_Devolucion_Compra AND Id_Plan_Cuenta = 328),0)
                        END
                        ) AS Neto
                        FROM Devolucion_Compra NC
                        INNER JOIN Proveedor C
                        ON C.Id_Proveedor = NC.Id_Proveedor
                        WHERE (DATE(NC.Fecha) BETWEEN '$fecha_inicio' AND '$fecha_fin') $condicion_nit
                        ORDER BY CONVERT(SUBSTRING(NC.Codigo, 4),UNSIGNED INTEGER)";

            break;

        case 'Acta_Compra':
            $fecha_inicio = '';
            $fecha_fin = '';
            if (isset($_REQUEST['fini']) && $_REQUEST['fini'] != "" && $_REQUEST['fini'] != "undefined") {
                $fecha_inicio = $_REQUEST['fini'];
            }
            if (isset($_REQUEST['ffin']) && $_REQUEST['ffin'] != "" && $_REQUEST['ffin'] != "undefined") {
                $fecha_fin = $_REQUEST['ffin'];
            }
            $query = "
                SELECT
                    AR.Codigo as Acta_Recepcion,  AR.Codigo,

                    DATE_FORMAT(AR.Fecha_Creacion,'%Y-%m-%d') as Fecha_Recepcion,


                    OCN.Id_Proveedor AS Nit,
                    P.Nombre AS Proveedor,
                    IF(AR.Id_Bodega = 0, 'Punto Dispensacion', 'Bodega') AS Tipo,
                    FAR.Factura,
                    FAR.Fecha_Factura,

                    (
                        CASE AR.Estado
                        WHEN 'Anulada' THEN 0
                        ELSE
                            (SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
                            FROM Producto_Acta_Recepcion PAR

                            WHERE PAR.Impuesto = 0 AND PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion )
                        END
                    ) as Valor_Excento,

                    (
                        CASE AR.Estado
                        WHEN 'Anulada' THEN 0
                        ELSE
                            (SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
                            FROM Producto_Acta_Recepcion PAR
                            WHERE PAR.Impuesto > 0  AND PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion  )
                        END
                    ) as Valor_Gravado,

                    (
                        CASE AR.Estado
                        WHEN 'Anulada' THEN 0
                        ELSE
                            (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio)*(PAR.Impuesto/100)),0)
                            FROM Producto_Acta_Recepcion PAR

                            WHERE PAR.Impuesto > 0  AND PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion  )
                        END
                    ) as Iva,

                    (
                        CASE AR.Estado
                        WHEN 'Anulada' THEN 0
                        ELSE
                            IFNULL((SELECT IF(FARR.Valor_Retencion != '' OR FARR.Valor_Retencion IS NOT NULL,FARR.Valor_Retencion, 0) FROM Factura_Acta_Recepcion_Retencion FARR WHERE FARR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND FARR.Id_Factura = FAR.Id_Factura_Acta_Recepcion AND FARR.Id_Retencion = 60 LIMIT 1),0)
                        END
                    ) AS Rte_Fuente,

                    (
                        CASE AR.Estado
                        WHEN 'Anulada' THEN 0
                        ELSE
                            IFNULL((SELECT IF(FARR.Valor_Retencion != '' OR FARR.Valor_Retencion IS NOT NULL,FARR.Valor_Retencion, 0) FROM Factura_Acta_Recepcion_Retencion FARR WHERE FARR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND FARR.Id_Factura = FAR.Id_Factura_Acta_Recepcion AND FARR.Id_Retencion = 63 LIMIT 1),0)
                        END
                    ) AS Rte_Ica,

                    (
                        CASE AR.Estado
                        WHEN 'Anulada' THEN 0
                        ELSE
                            ((SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
                            FROM Producto_Acta_Recepcion PAR

                            WHERE PAR.Impuesto > 0  AND PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion )
                            +
                            (SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
                            FROM Producto_Acta_Recepcion PAR

                            WHERE  PAR.Impuesto = 0  AND PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion ) )
                        END
                    ) as Total_Compra,

                    (
                        CASE AR.Estado
                        WHEN 'Anulada' THEN 0
                        ELSE
                            IFNULL(((SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
                            FROM Producto_Acta_Recepcion PAR
                             WHERE PAR.Impuesto > 0  AND PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion )
                            +
                            (SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
                            FROM Producto_Acta_Recepcion PAR
                             WHERE PAR.Impuesto = 0  AND PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion )
                            +
                            (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio)*(19/100)),0)
                            FROM Producto_Acta_Recepcion PAR
                             WHERE PAR.Impuesto > 0  AND PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion )
                            -
                            IFNULL((SELECT IF(FARR.Valor_Retencion != '' OR FARR.Valor_Retencion IS NOT NULL,FARR.Valor_Retencion, 0)
                            FROM Factura_Acta_Recepcion_Retencion FARR WHERE FARR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND FARR.Id_Factura =
                            FAR.Id_Factura_Acta_Recepcion AND FARR.Id_Retencion = 60 LIMIT 1),0)
                            -
                            IFNULL((SELECT IF(FARR.Valor_Retencion != '' OR FARR.Valor_Retencion IS NOT NULL,FARR.Valor_Retencion, 0)
                            FROM Factura_Acta_Recepcion_Retencion FARR WHERE FARR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND FARR.Id_Factura =
                            FAR.Id_Factura_Acta_Recepcion AND FARR.Id_Retencion = 63 LIMIT 1),0)
                            ),0)
                        END
                    ) AS Neto_Factura,

                    AR.Estado

                    FROM Orden_Compra_Nacional OCN
                    INNER JOIN Acta_Recepcion AR  ON AR.Id_Orden_Compra_Nacional = OCN.Id_Orden_Compra_Nacional
                    INNER JOIN Factura_Acta_Recepcion FAR ON FAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion
                    INNER JOIN Proveedor P ON P.Id_Proveedor = OCN.Id_Proveedor
                    WHERE DATE_FORMAT(AR.Fecha_Creacion, '%Y-%m-%d') BETWEEN '" . $fecha_inicio . "' AND '" . $fecha_fin . "'" . $condicion_nit . "
                    ORDER BY CONVERT(SUBSTRING(AR.Codigo, 4),UNSIGNED INTEGER) ";

            break;

        case 'Reporte_Nacionalizacion':
            $fecha_inicio = '';
            $fecha_fin = '';
            if (isset($_REQUEST['fini']) && $_REQUEST['fini'] != "" && $_REQUEST['fini'] != "undefined") {
                $fecha_inicio = $_REQUEST['fini'];
            }
            if (isset($_REQUEST['ffin']) && $_REQUEST['ffin'] != "" && $_REQUEST['ffin'] != "undefined") {
                $fecha_fin = $_REQUEST['ffin'];
            }
            $query = '
                SELECT
                PRD.Nombre_Comercial, "" as Codigo,
                IFNULL(CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," ", PRD.Cantidad," ", PRD.Unidad_Medida),
                    CONCAT(PRD.Nombre_Comercial," ",PRD.Laboratorio_Comercial)) as Producto,
                IF(PRD.Laboratorio_Generico IS NULL,PRD.Laboratorio_Comercial,PRD.Laboratorio_Generico) as Laboratorio,
                PRD.Embalaje,
                PRD.Codigo_Cum,
                IF((PRO.Primer_Nombre IS NULL OR PRO.Primer_Nombre = ""), PRO.Nombre, CONCAT_WS(" ", PRO.Primer_Nombre, PRO.Segundo_Nombre, PRO.Primer_Apellido, PRO.Segundo_Apellido)) AS Nombre_Proveedor,
                OCI.Codigo AS Codigo_Compra,
                ARI.Codigo AS Codigo_Acta,
                NP.Codigo AS Codigo_Nacionalizacion,
                NP.Fecha_Registro,
                NP.Tasa_Cambio AS Tasa,
                NP.Tramite_Sia,
                NP.Formulario,
                NP.Cargue,
                NP.Gasto_Bancario,
                NP.Descuento_Parcial AS Descuento_Arancelario,
                PNP.Total_Flete AS Flete_Internacional_USD,
                PNP.Total_Seguro AS Seguro_Internacional_USD,
                PNP.Total_Flete_Nacional,
                PNP.Total_Licencia,
                PNP.Total_Arancel,
                PNP.Total_Iva,
                PNP.Subtotal AS Subtotal_Importacion,
                (PNP.Subtotal+PNP.Total_Flete_Nacional+PNP.Total_Licencia+PNP.Total_Iva) AS Subtotal_Nacionalizacion
            FROM Nacionalizacion_Parcial NP
            INNER JOIN Producto_Nacionalizacion_Parcial PNP ON NP.Id_Nacionalizacion_Parcial = PNP.Id_Nacionalizacion_Parcial
            INNER JOIN Acta_Recepcion_Internacional ARI ON NP.Id_Acta_Recepcion_Internacional = ARI.Id_Acta_Recepcion_Internacional
            INNER JOIN Orden_Compra_Internacional OCI ON ARI.Id_Orden_Compra_Internacional = OCI.Id_Orden_Compra_Internacional
            INNER JOIN Producto PRD ON PNP.Id_Producto = PRD.Id_Producto
            INNER JOIN Funcionario F ON NP.Identificacion_Funcionario = F.Identificacion_Funcionario
            INNER JOIN Proveedor PRO ON OCI.Id_Proveedor = PRO.Id_Proveedor
            WHERE DATE_FORMAT(NP.Fecha_Registro, "%Y-%m-%d") BETWEEN "' . $fecha_inicio . '" AND "' . $fecha_fin . '"' . $condicion_nit;

            break;
        case 'Dispensacion':
            $fecha_inicio = '';
            $fecha_fin = '';
            if (isset($_REQUEST['fini']) && $_REQUEST['fini'] != "" && $_REQUEST['fini'] != "undefined") {
                $fecha_inicio = $_REQUEST['fini'];
            }
            if (isset($_REQUEST['ffin']) && $_REQUEST['ffin'] != "" && $_REQUEST['ffin'] != "undefined") {
                $fecha_fin = $_REQUEST['ffin'];
            }

            $query = "SELECT
            DATE_FORMAT(Fecha_Actual, '%d/%m/%Y') AS Fecha,
            Codigo AS Dispensacion, Codigo,
            Tipo,
            IF(Tipo NOT IN ('Evento' , 'Cohortes', 'Capita'),
                (SELECT
                        Nombre
                    FROM
                        Tipo_Servicio
                    WHERE
                        Id_Tipo_Servicio = D.Tipo_Servicio),
                '') AS Tipo_Servicio,
            EPS AS Eps_Punto_Dispensacion,
            IF(Tipo IN ('Evento','Cohortes','NoPos') AND PC.Id_Regimen = 1, PC.Nit, PT.Nit) AS Nit_Cobrar,
            IF(Tipo IN ('Evento','Cohortes','NoPos') AND PC.Id_Regimen = 1, PC.Cliente_Cobrar, PT.Cliente_Cobrar) AS Cliente_Cobrar,
            PT.Nombre AS Punto_Dispensacion,
            PD.Costo_Total,
            Estado_Facturacion,
            Estado_Dispensacion AS Estado
            FROM
            Dispensacion D
                INNER JOIN
            (SELECT
                PD2.Id_Dispensacion,
                    SUM(I.Costo * PD2.Cantidad_Entregada) AS Costo_Total
            FROM
                Producto_Dispensacion PD2
            INNER JOIN (SELECT
                Id_Producto, ROUND(AVG(Costo),2) AS Costo
            FROM
                Inventario_Viejo
            WHERE
                Costo > 0 AND Id_Bodega != 0
            GROUP BY Id_Producto) I ON I.Id_Producto = PD2.Id_Producto
            GROUP BY PD2.Id_Dispensacion) PD ON PD.Id_Dispensacion = D.Id_Dispensacion
            INNER JOIN (SELECT Id_Paciente, Id_Regimen, Nit, (SELECT Razon_Social FROM Cliente WHERE Id_Cliente = P.Nit) AS Cliente_Cobrar FROM Paciente P) PC ON PC.Id_Paciente = D.Numero_Documento
            INNER JOIN (SELECT Id_Punto_Dispensacion, PT2.Nombre, DC.Id_Cliente AS Nit, (SELECT Nombre FROM Cliente WHERE Id_Cliente = DC.Id_Cliente) AS Cliente_Cobrar FROM Punto_Dispensacion PT2 LEFT JOIN Departamento_Cliente DC ON PT2.Departamento = DC.Id_Departamento) PT ON PT.Id_Punto_Dispensacion = D.Id_Punto_Dispensacion
            WHERE
            DATE(Fecha_Actual) BETWEEN '$fecha_inicio' AND '$fecha_fin' AND D.Estado_Dispensacion!='Anulada' $condicion_nit

            ORDER BY CONVERT(SUBSTRING(D.Codigo, 4),UNSIGNED INTEGER) ";

            break;

        case 'Dispensacion_Pendientes':
            $fecha_inicio = '';
            $fecha_fin = '';
            if (isset($_REQUEST['fini']) && $_REQUEST['fini'] != "" && $_REQUEST['fini'] != "undefined") {
                $fecha_inicio = $_REQUEST['fini'];
            }
            if (isset($_REQUEST['ffin']) && $_REQUEST['ffin'] != "" && $_REQUEST['ffin'] != "undefined") {
                $fecha_fin = $_REQUEST['ffin'];
            }
            if ($_REQUEST['nit'] && $_REQUEST['nit'] != '') {
                $condicion_nit = ' AND PA.Nit="' . $_REQUEST['nit'] . '"';
            }

            $query = "SELECT
            DATE(D.Fecha_Actual) AS Fecha_Dispensacion,
            D.Codigo,
            D.EPS,PA.Nit,
            D.Estado_Dispensacion,
            SUM( PD.Cantidad_Entregada * COALESCE( IF( PD.Costo != NULL AND PD.Costo != 0 , PD.Costo , NULL) , I.Costo_Promedio , 0 )  ) AS Entergado_Sin_Facturar,
            SUM( (PD.Cantidad_Formulada - PD.Cantidad_Entregada) * COALESCE( IF( PD.Costo != NULL AND PD.Costo != 0 , PD.Costo , NULL), I.Costo_Promedio , 0 )  ) AS Sin_Entregar,
            (SELECT
                    Nombre
                FROM
                    Servicio
                WHERE
                    Id_Servicio = D.Id_Servicio) AS Servicio,
            T.Nombre AS Tipo_Servicio
            FROM
                Dispensacion D
                    INNER JOIN
                Producto_Dispensacion PD ON D.Id_Dispensacion = PD.Id_Dispensacion

                    INNER JOIN
                Tipo_Servicio T ON D.Id_Tipo_Servicio = T.Id_Tipo_Servicio

                LEFT JOIN Costo_Promedio I ON I.Id_Producto = PD.Id_Producto
                INNER JOIN ( SELECT Id_Paciente, Nit FROM Paciente ) PA ON D.Numero_Documento=PA.Id_Paciente

            WHERE
                DATE(Fecha_Actual) BETWEEN '$fecha_inicio' AND '$fecha_fin' AND D.Estado_Facturacion!='Facturada'
            AND D.Estado_Dispensacion != 'Anulada' $condicion_nit GROUP BY PD.Id_Dispensacion

            ORDER BY Fecha_Actual ASC";

            break;

        case 'Dispensacion_Cuotas':

            $fecha_inicio = '';
            $fecha_fin = '';
            if (isset($_REQUEST['fini']) && $_REQUEST['fini'] != "" && $_REQUEST['fini'] != "undefined") {
                $fecha_inicio = $_REQUEST['fini'];
            }
            if (isset($_REQUEST['ffin']) && $_REQUEST['ffin'] != "" && $_REQUEST['ffin'] != "undefined") {
                $fecha_fin = $_REQUEST['ffin'];
            }

            $query = "SELECT Codigo, DATE(Fecha_Actual) AS Fecha_Solicitud, EPS, PT.Nombre AS Punto_Dispensacion, PT.Departamento, IF(Tipo NOT IN('Capita','Evento','Cohortes'),(SELECT Nombre FROM Tipo_Servicio WHERE Id_Tipo_Servicio = D.Tipo_Servicio),Tipo) AS Tipo_Servicio, IF(D.Estado_Dispensacion != 'Anulada',IF(P.Id_Regimen = 1, Cuota, 0),0) AS Cuota_Moderadora, IF(D.Estado_Dispensacion != 'Anulada',IF(P.Id_Regimen = 2, Cuota, 0),0) AS Cuota_Recuperacion, D.Estado_Dispensacion AS Estado FROM Dispensacion D INNER JOIN (SELECT Id_Punto_Dispensacion, Nombre, (SELECT Nombre FROM Departamento WHERE Id_Departamento = PT2.Departamento) AS Departamento FROM Punto_Dispensacion PT2) PT ON PT.Id_Punto_Dispensacion = D.Id_Punto_Dispensacion INNER JOIN (SELECT Id_Paciente, Id_Regimen, Nit FROM Paciente) P ON P.Id_Paciente = D.Numero_Documento WHERE DATE(Fecha_Actual) BETWEEN '$fecha_inicio' AND '$fecha_fin'";

            break;
        case 'Terceros':

            $query = "(SELECT Id_Proveedor AS Nit, Digito_Verificacion, Tipo AS Tipo_Persona, Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido, Razon_Social, Nombre AS Nombre_Comercial, Direccion, Telefono AS Telefono_Fijo, Celular AS Telefono_Celular, Correo, (SELECT Nombre FROM Departamento WHERE Id_Departamento = P.Id_Departamento) AS Departamento, (SELECT Nombre FROM Municipio WHERE Id_Municipio = P.Id_Municipio) AS Municipio, Regimen AS Tipo_Regimen, Tipo_Retencion, Animo_Lucro, Ley_1429_2010, (SELECT Descripcion FROM Codigo_Ciiu WHERE Id_Codigo_Ciiu = P.Id_Codigo_Ciiu) AS Actividad_Economica, Tipo_Reteica, Contribuyente AS Gran_Contribuyente, IF(Condicion_Pago IN (0,1), 'Contado', CONCAT(Condicion_Pago,' Días')) AS Plazo, Estado, Tipo_Tercero FROM Proveedor P)
            UNION
            (SELECT Id_Cliente AS Nit, Digito_Verificacion, Tipo AS Tipo_Persona, Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido, Razon_Social, Nombre AS Nombre_Comercial, Direccion, Telefono_Persona_Contacto AS Telefono_Fijo, Celular AS Telefono_Celular, Correo_Persona_Contacto AS Correo, (SELECT Nombre FROM Departamento WHERE Id_Departamento = P.Id_Departamento) AS Departamento, (SELECT Nombre FROM Municipio WHERE Id_Municipio = P.Id_Municipio) AS Municipio, Regimen AS Tipo_Regimen, '' AS Tipo_Retencion, Animo_Lucro, '' AS Ley_1429_2010, (SELECT Descripcion FROM Codigo_Ciiu WHERE Id_Codigo_Ciiu = P.Id_Codigo_Ciiu) AS Actividad_Economica, Tipo_Reteica, Contribuyente AS Gran_Contribuyente, IF(Condicion_Pago IN (0,1), 'Contado', CONCAT(Condicion_Pago,' Días')) AS Plazo, Estado, 'Cliente' FROM Cliente P)
            UNION
            (SELECT P.Identificacion_Funcionario AS Nit, '' AS Digito_Verificacion, 'Natural' AS Tipo_Persona, Primer_Nombre, Segundo_Nombre, Primer_Apellido, Segundo_Apellido, '' AS Razon_Social, '' AS Nombre_Comercial, Direccion_Residencia AS Direccion, Telefono AS Telefono_Fijo, Celular AS Telefono_Celular, Correo, (SELECT Nombre FROM Departamento WHERE Id_Departamento = M.Id_Departamento) AS Departamento, M.Nombre_Municipio AS Municipio, '' AS Tipo_Regimen, '' AS Tipo_Retencion, '' AS Animo_Lucro, '' AS Ley_1429_2010, '' AS Actividad_Economica, '' AS Tipo_Reteica, '' AS Gran_Contribuyente, '' AS Plazo, IF(Autorizado = 'Si','Activo','Inactivo') AS Estado, 'Funcionario' FROM Funcionario P INNER JOIN Contrato_Funcionario FC ON P.Identificacion_Funcionario = FC.Identificacion_Funcionario INNER JOIN (SELECT T.Id_Municipio, Nombre AS Nombre_Municipio, Id_Departamento FROM Municipio T) M ON FC.Id_Municipio = M.Id_Municipio)
            UNION
            (SELECT Nit, '' AS Digito_Verificacion, 'Juridico' AS Tipo_Persona, '' AS Primer_Nombre, '' AS Segundo_Nombre, '' AS Primero_Apellido, '' AS Segundo_Apellido, Nombre AS Razon_Social, Nombre AS Nombre_Comercial, '' AS Direccion, '' AS Telefono, '' AS Celular, '' AS Correo, '' AS Departamento, '' AS Nombre_Municipio, '' AS Tipo_Regimen, '' AS Tipo_Retencion, '' AS Animo_Lucro, '' AS Ley_1429_2010, '' AS Actividad_Economica, '' AS Tipo_Reteica, '' AS Gran_Contribuyente, '' AS Plazo, 'Activo' AS Estado, 'Caja_Compensacion' AS Tipo FROM Caja_Compensacion WHERE Nit IS NOT NULL)";

            break;
        case 'Reporte_Exentos':

            $fecha_inicio = '';
            $fecha_fin = '';
            if (isset($_REQUEST['fini']) && $_REQUEST['fini'] != "" && $_REQUEST['fini'] != "undefined") {
                $fecha_inicio = $_REQUEST['fini'];
            }
            if (isset($_REQUEST['ffin']) && $_REQUEST['ffin'] != "" && $_REQUEST['ffin'] != "undefined") {
                $fecha_fin = $_REQUEST['ffin'];
            }
            $query = 'SELECT F.Codigo, F.Fecha_Documento AS Fecha, P.Nombre_Comercial, P.Laboratorio_Comercial, PF.Cantidad, PF.Precio, PF.Descuento, PF.Impuesto, PF.Subtotal
            FROM Producto_Factura PF
            INNER JOIN Factura F ON F.Id_Factura = PF.Id_Factura
            INNER JOIN Producto P ON P.Id_Producto = PF.Id_Producto
            WHERE P.Laboratorio_Comercial LIKE "%Exentos%"
            AND PF.Impuesto = 0
            AND  DATE(F.Fecha_Documento) BETWEEN "' . $fecha_inicio . '" AND  "' . $fecha_fin . '"


            UNION ALL

            SELECT F.Codigo, F.Fecha_Documento AS Fecha, P.Nombre_Comercial, P.Laboratorio_Comercial, PF.Cantidad, PF.Precio_Venta, PF.Descuento, PF.Impuesto, PF.Subtotal
            FROM Producto_Factura_Venta PF
            INNER JOIN Factura_Venta F ON F.Id_Factura_Venta = PF.Id_Factura_Venta
            INNER JOIN Producto P ON P.Id_Producto = PF.Id_Producto
            WHERE P.Laboratorio_Comercial LIKE "%Exentos%"
            AND PF.Impuesto = 0
            AND  DATE(F.Fecha_Documento) BETWEEN "' . $fecha_inicio . '" AND  "' . $fecha_fin . '"
            ORDER BY Fecha';

            break;

        case 'Nota_Credito_Global';
            $fecha_inicio = '';
            $fecha_fin = '';
            if (isset($_REQUEST['fini']) && $_REQUEST['fini'] != "" && $_REQUEST['fini'] != "undefined") {
                $fecha_inicio = $_REQUEST['fini'];
            }
            if (isset($_REQUEST['ffin']) && $_REQUEST['ffin'] != "" && $_REQUEST['ffin'] != "undefined") {
                $fecha_fin = $_REQUEST['ffin'];
            }

            $query = "SELECT
                    NG.Codigo, NG.Fecha,
                    CONCAT( IFNULL(F.Nombres,CONCAT( F.Primer_Nombre, F.Segundo_Nombre ) ) , ' ',
                    IFNULL(F.Apellidos,CONCAT( F.Primer_Apellido, F.Segundo_Apellido ) )    ) AS  Funcionario_Nota,
                    NG.Codigo_Factura, REPLACE(NG.Tipo_Factura, '_' ,' ')AS Tipo_Factura,
                    NG.Id_Cliente as NIT,  NG.Valor_Total_Factura,
                    SUM(  ( (P.Impuesto)/100) * ( P.Cantidad * (P.Precio_Nota_Credito) )  )  AS Total_Iva,
                    SUM(P.Valor_Nota_Credito) AS Valor_Nota_Credito , P.Observacion
                    FROM Nota_Credito_Global NG
                    INNER JOIN Producto_Nota_Credito_Global P ON P.Id_Nota_Credito_Global = NG.Id_Nota_Credito_Global
                    INNER JOIN Funcionario F ON F.Identificacion_Funcionario = NG.Id_Funcionario
                    WHERE  DATE(NG.Fecha) BETWEEN '$fecha_inicio' AND  '$fecha_fin'
                    #AND NG.Procesada = 'True' no es posible procesar la notas credito debido a un error en la facturacion electronica 
                    GROUP BY NG.Id_Nota_Credito_Global
                    ORDER BY  CONVERT(SUBSTRING(NG.Codigo, 6),UNSIGNED INTEGER)
                    ";

            break;
        case 'Compra_Pai':
            $fecha_inicio = '';
            $fecha_fin = '';
            if (isset($_REQUEST['fini']) && $_REQUEST['fini'] != "" && $_REQUEST['fini'] != "undefined") {
                $fecha_inicio = $_REQUEST['fini'];
            }
            if (isset($_REQUEST['ffin']) && $_REQUEST['ffin'] != "" && $_REQUEST['ffin'] != "undefined") {
                $fecha_fin = $_REQUEST['ffin'];
            }
            $query = "
                SELECT
                    AR.Codigo as Acta_Recepcion,  AR.Codigo,
                    DATE_FORMAT(AR.Fecha_Creacion,'%Y-%m-%d') as Fecha_Recepcion,
                    OCN.Id_Proveedor AS Nit,
                    P.Nombre AS Proveedor,
                    IF(AR.Id_Bodega = 0, 'Punto Dispensacion', 'Bodega') AS Tipo,
                    FAR.Factura,
                    FAR.Fecha_Factura,

                    (
                        CASE AR.Estado
                        WHEN 'Anulada' THEN 0
                        ELSE
                            (SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
                            FROM Producto_Acta_Recepcion PAR
                            WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND PAR.Impuesto=0)
                        END
                    ) as Valor_Excento,

                    (
                        CASE AR.Estado
                        WHEN 'Anulada' THEN 0
                        ELSE
                            (SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
                            FROM Producto_Acta_Recepcion PAR

                            WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND PAR.Impuesto!=0)
                        END
                    ) as Valor_Gravado,

                    (
                        CASE AR.Estado
                        WHEN 'Anulada' THEN 0
                        ELSE
                            (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio)*(19/100)),0)
                            FROM Producto_Acta_Recepcion PAR

                            WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND PAR.Impuesto!=0)
                        END
                    ) as Iva,

                    (
                        CASE AR.Estado
                        WHEN 'Anulada' THEN 0
                        ELSE
                            IFNULL((SELECT IF(FARR.Valor_Retencion != '' OR FARR.Valor_Retencion IS NOT NULL,FARR.Valor_Retencion, 0) FROM Factura_Acta_Recepcion_Retencion FARR WHERE FARR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND FARR.Id_Factura = FAR.Id_Factura_Acta_Recepcion AND FARR.Id_Retencion = 60 LIMIT 1),0)
                        END
                    ) AS Rte_Fuente,

                    (
                        CASE AR.Estado
                        WHEN 'Anulada' THEN 0
                        ELSE
                            IFNULL((SELECT IF(FARR.Valor_Retencion != '' OR FARR.Valor_Retencion IS NOT NULL,FARR.Valor_Retencion, 0) FROM Factura_Acta_Recepcion_Retencion FARR WHERE FARR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND FARR.Id_Factura = FAR.Id_Factura_Acta_Recepcion AND FARR.Id_Retencion = 63 LIMIT 1),0)
                        END
                    ) AS Rte_Ica,

                    (
                        CASE AR.Estado
                        WHEN 'Anulada' THEN 0
                        ELSE
                            ((SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
                            FROM Producto_Acta_Recepcion PAR

                            WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND PAR.Impuesto!=0)
                            +
                            (SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
                            FROM Producto_Acta_Recepcion PAR

                            WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND PAR.Impuesto=0))
                        END
                    ) as Total_Compra,

                    (
                        CASE AR.Estado
                        WHEN 'Anulada' THEN 0
                        ELSE
                            IFNULL( ( (SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
                            FROM Producto_Acta_Recepcion PAR

                            WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND PAR.Impuesto!=0)
                            +
                            (SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
                            FROM Producto_Acta_Recepcion PAR
                             WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND PAR.Impuesto=0)
                            +
                            (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio)*(19/100)),0)
                            FROM Producto_Acta_Recepcion PAR

                            WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND PAR.Impuesto!=0)
                            -
                            IFNULL((SELECT IF(FARR.Valor_Retencion != '' OR FARR.Valor_Retencion IS NOT NULL,FARR.Valor_Retencion, 0) FROM Factura_Acta_Recepcion_Retencion FARR WHERE FARR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND FARR.Id_Factura = FAR.Id_Factura_Acta_Recepcion AND FARR.Id_Retencion = 60 LIMIT 1),0)
                            -
                            IFNULL((SELECT IF(FARR.Valor_Retencion != '' OR FARR.Valor_Retencion IS NOT NULL,FARR.Valor_Retencion, 0) FROM Factura_Acta_Recepcion_Retencion FARR WHERE FARR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND FARR.Id_Factura = FAR.Id_Factura_Acta_Recepcion AND FARR.Id_Retencion = 63 LIMIT 1),0)
                            ),0)
                        END
                    ) AS Neto_Factura,


                    AR.Estado,
                    'Compras' AS Tipo_Reporte

                        FROM Orden_Compra_Nacional OCN
                        INNER JOIN Acta_Recepcion AR  ON AR.Id_Orden_Compra_Nacional = OCN.Id_Orden_Compra_Nacional
                        INNER JOIN Factura_Acta_Recepcion FAR ON FAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion
                        INNER JOIN Proveedor P ON P.Id_Proveedor = OCN.Id_Proveedor
                        WHERE DATE_FORMAT(AR.Fecha_Creacion, '%Y-%m-%d') BETWEEN '" . $fecha_inicio . "' AND '" . $fecha_fin . "'" . $condicion_nit . "

                UNION ALL
                (
                 SELECT

                    ARI.Codigo as Acta_Recepcion,  NP.Codigo,
                    DATE_FORMAT(NP.Fecha_Registro,'%Y-%m-%d') as Fecha_Recepcion,
                    ARI.Id_Proveedor AS Nit,
                    P.Nombre AS Proveedor,
                    'Internacional' AS Tipo,
                    FAR.Factura,
                    FAR.Fecha_Factura,


                    (
                        CASE NP.Estado
                            WHEN 'Anulada' THEN 0
                            ELSE IFNULL(
                                ROUND(SUM( IF(PNP.Total_Iva=0, PNP.Precio_Unitario_Pesos * PNP.Cantidad , 0 )  ),2), 0)
                        END
                    ) AS Valor_Excento,
                    (
                        CASE NP.Estado
                            WHEN 'Anulada' THEN 0
                            ELSE IFNULL(
                                ROUND(SUM( IF( PNP.Total_Iva>=0, PNP.Precio_Unitario_Pesos * PNP.Cantidad , 0 ) ),2),0)
                        END
                    ) AS Valor_Gravado,

                    (
                        CASE NP.Estado
                            WHEN 'Anulada' THEN 0
                            ELSE IFNULL(SUM(  PNP.Total_Iva  ),0)
                        END
                    ) AS Iva,

                    0 AS Rte_Fuente,
                    0 AS Rte_Ica,

                    (
                        CASE NP.Estado
                            WHEN 'Anulada' THEN 0
                            ELSE IFNULL(
                                ROUND(SUM( PNP.Precio_Unitario_Pesos * PNP.Cantidad  ),2),0)
                        END
                    ) AS Total_Compra,
                    (
                        CASE NP.Estado
                            WHEN 'Anulada' THEN 0
                            ELSE IFNULL(SUM( PNP.Subtotal  ),0)
                        END
                    ) AS Neto,

                    NP.Estado,
                    'Parcial Internacional' AS Tipo_Reporte


                 FROM Nacionalizacion_Parcial NP
                 INNER JOIN Producto_Nacionalizacion_Parcial PNP ON PNP.Id_Nacionalizacion_Parcial =  NP.Id_Nacionalizacion_Parcial
                 INNER JOIN Producto PR ON PR.Id_Producto = PNP.Id_Producto
                 INNER JOIN Acta_Recepcion_Internacional ARI ON ARI.Id_Acta_Recepcion_Internacional = NP.Id_Acta_Recepcion_Internacional
                 #INNER JOIN Producto_Acta_Recepcion_Internacional PAN ON PAN.Id_Producto_Acta_Recepcion_Internacional = PNP.Id_Producto_Acta_Recepcion_Internacional

                 INNER JOIN Factura_Acta_Recepcion_Internacional FAR ON FAR.Id_Acta_Recepcion_Internacional = ARI.Id_Acta_Recepcion_Internacional
                 INNER JOIN Proveedor P ON P.Id_Proveedor = ARI.Id_Proveedor
                 GROUP BY NP.Id_Nacionalizacion_Parcial
                )






                #ORDER BY  CONVERT(SUBSTRING(Codigo, 4),UNSIGNED INTEGER) ";
            # echo $query;exit;
            break;
    }
    return is_array($query) ? $query : [$query]; //Si $query ya es un arreglo, se devuelve tal cual; si no, se envuelve en uno. Reemplaza el uso de explode(...) y elimina dependencias del delimitador '...'.
}

function ValidarKey($key)
{
    $datos = [
        "Total_Impuesto", "Nada", "Excenta", "Valor_Excento", "Valor_Gravado",
        "Rte_Fuente", "Rte_Ica", "Total_Compra", "Iva", "Descuentos", "Total_Venta",
        "Neto_Factura", "Costo_Venta_Exenta", "Costo_Venta_Gravada", "Gravado", "Total",
        "Excento", "Total_Factura", "Gravada", "Valorizado", "Tramite_Sia", "Formulario",
        "Cargue", "Gasto_Bancario", "Descuento Arancelario", "Flete_Internacional_USD",
        "Seguro_Internacional_USD", "Total_Flete_Nacional", "Total_Licencia", "Total_Arancel",
        "Total_Iva", "Subtotal_Importacion", "Subtotal_Nacionalizacion", "Tasa", "Costo_Total",
        "Rentabilidad", "Total_Costo_Venta", "Cuota_Moderadora", "Cuota_Recuperacion", "Neto", "Total", "Valor_Total_Factura", "Valor_Nota_Credito", "Precio", "Descuento", "Subtotal"
    ];
    $pos = array_search($key, $datos);
    return strval($pos);
}

function ValidarCodigos($key)
{
    $datos = ["Ventas", "DevolucionV", "DevolucionC", "Acta_Compra" /*,"Nota_Credito_Global"*/];
    $res = in_array($key, $datos);
    return strval($res);
}
