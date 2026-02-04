<?php

// Configurar reporte de errores
error_reporting(E_ALL);
ini_set("display_errors", "On");

// Configurar headers para CORS y tipo de contenido
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

// Incluir clases necesarias
include_once '../../class/class.querybasedatos.php';
include_once '../../class/class.http_response.php';
require_once '../../class/class.configuracion.php';
include_once '../../class/class.portal_clientes.php';
include_once '../../class/class.facturaccionmasiva.php';
include_once '../../class/class.mipres.php';
require '../../class/class.awsS3.php';

// Incluir helpers
include_once '../../helper/response.php';

// Instanciar clases necesarias
$queryObj = new QueryBaseDatos(); // Objeto para consultas a base de datos
$response = array(); // Arreglo para la respuesta
$http_response = new HttpResponse(); // Objeto para manejar respuestas HTTP

$configuracion = new Configuracion(); // Objeto de configuración
$portalClientes = new PortalCliente($queryObj); // Objeto para portal de clientes
$facturaccion = new Facturacion_Masiva(); // Objeto para facturación masiva
$mipres = new Mipres(); // Objeto para Mipres
$id_disp = ''; // Variable para ID de dispensación

// Establecer zona horaria
date_default_timezone_set('America/Bogota');

// Obtener datos del formulario
$modelo = (isset($_REQUEST['modelo']) ? $_REQUEST['modelo'] : '');
$productos = (isset($_REQUEST['productos']) ? $_REQUEST['productos'] : '');
$reclamante = (isset($_REQUEST['reclamante']) ? $_REQUEST['reclamante'] : '');

// Convertir a UTF-8
$modelo = mb_convert_encoding($modelo, 'UTF-8');

// Decodificar datos JSON a arrays
$modelo = (array) json_decode($modelo);
$productos = (array) json_decode(mb_convert_encoding($productos, "UTF-8"), true);
$reclamante = (array) json_decode(mb_convert_encoding($reclamante, 'UTF-8'), true);

// Variables para almacenar información
$productos_no_entregados = []; // Productos no entregados
$eventos_positiva = []; // Eventos para Positiva
$imagen = $modelo["Firma_Reclamante"]; // Firma del reclamante
$fot = ''; // Variable para guardar ruta de firma

// Procesar firma del reclamante si existe
if ($imagen != '') {
    $fot = SaveFirma($imagen); // Guardar firma y obtener ruta
    $oItem = new complex('Dispensacion', 'Id_Dispensacion', $modelo['Id_Dispensacion']); // Crear objeto de dispensación
    $oItem->Firma_Reclamante = $fot; // Asignar firma
    $oItem->save(); // Guardar cambios
    unset($oItem); // Liberar memoria
}

$modelo["Firma_Reclamante"] = $fot; // Actualizar modelo con ruta de firma

// Intentar guardar acta de entrega en AWS S3
try {
    if (!empty($_FILES['acta_entrega']['name'])) { // Verificar si hay archivo
        $s3 = new AwsS3(); // Instanciar cliente S3
        $ruta = "dispensacion/auditoria/soportes/acta_entrega"; // Ruta de almacenamiento
        $nombre_archivo = $s3->putObject($ruta, $_FILES['acta_entrega']); // Subir archivo
        $modelo["Acta_Entrega"] = $nombre_archivo; // Guardar nombre en modelo
    }
} catch (\Throwable $th) { // Capturar errores
    http_response_code(500); // Error interno del servidor
    echo $th->getMessage(); // Mostrar mensaje de error
}

$idFactura = 0; // Inicializar ID de factura

// Asignar ID de dispensación a productos para dispensación pendiente
foreach ($productos as &$p) {
    $p['Id_Dispensacion'] = $modelo['Id_Dispensacion'];
}
unset($p); // Romper referencia

// Intentar guardar productos de dispensación
try {
    if ($modelo['Identificacion_Funcionario'] != '1099375816') { // Validar funcionario
        SaveProductosDispensacion($productos); // Guardar productos
    }
} catch (\Throwable $th) { // Capturar errores
    http_response_code(500); // Error interno del servidor
    echo $th->getMessage(); // Mostrar mensaje de error
    exit; // Detener ejecución
}

// Determinar respuesta según productos entregados
if (count($productos_no_entregados) == count($productos)) {
    $http_response->SetRespuesta(2, 'Guardado con Advertencia', 'No se Entregaron todos los pendientes (No hay cantidades suficientes) ');
    $response = $http_response->GetRespuesta();
    $response['productos_no_entregados'] = $productos_no_entregados;
} elseif (getStatus() == 1) {
    $http_response->SetRespuesta(0, 'Guardado Correctamente', 'Se ha guardado correctamente la dispensacion pendiente.');
    $response = $http_response->GetRespuesta();
    $response['productos_no_entregados'] = $productos_no_entregados;
} else {
    $http_response->SetRespuesta(0, 'Guardado Correctamente', 'Se ha guardado correctamente la dispensacion pendiente.');
    $response = $http_response->GetRespuesta();
    $response['productos_no_entregados'] = $productos_no_entregados;
}

// Agregar eventos de Positiva a la respuesta
$response['eventos_positiva'] = $eventos_positiva;

// Validar dispensación Mipres si aplica
if ($modelo['Id_Tipo_Servicio'] == 3 && $modelo['Id_Servicio'] == 2) {
    ValidarDispensacionMipres($modelo['Id_Dispensacion']);
}

// Agregar ID de factura a la respuesta
$response['id_factura'] = $idFactura;

// Devolver respuesta como JSON
echo json_encode($response);


/**
 * @param mixed $prod
 * 
 * @return [type]
 */


function SaveProductosDispensacion($prod)
{
    // Declarar variables globales para poder modificar arrays externos
    global $productos_no_entregados, $eventos_positiva;//ninguna de estas variables esta en uso

    // Recorrer cada producto en el array recibido
    foreach ($prod as $p) {

        // Completar datos faltantes desde el primer lote cuando no vienen en el nivel raíz
        if ((!isset($p['Id_Inventario_Nuevo']) || $p['Id_Inventario_Nuevo'] === '') && isset($p['Lotes'][0]['Id_Inventario_Nuevo'])) {
            $p['Id_Inventario_Nuevo'] = $p['Lotes'][0]['Id_Inventario_Nuevo'];
        }
        if ((!isset($p['Lote']) || $p['Lote'] === '') && isset($p['Lotes'][0]['Lote'])) {
            $p['Lote'] = $p['Lotes'][0]['Lote'];
        }
        // Normalizar id de inventario para evitar consultas vacías
        $p['Id_Inventario_Nuevo'] = isset($p['Id_Inventario_Nuevo']) ? (int) $p['Id_Inventario_Nuevo'] : 0;

        // Verificar si la dispensación ya ha sido facturada (no se puede modificar si está facturada)
        $facturada = ValidarDispensacionFacturacion($p['Id_Dispensacion']);

        // Solo procesar si NO está facturada
        if (!$facturada) {
            
            // CASO 1: Productos con lotes específicos seleccionados
            if (isset($p['Seleccionados'])) {
                
                // Obtener información actual del producto en dispensación
                $prod_dispensacion = GetProducto($p);
                
                // Validar disponibilidad de los lotes seleccionados
                $validacion_disponibles = validarDisponibilidadOtros($p['Seleccionados'], $prod_dispensacion);
                
                // Si hay disponibilidad y el producto existe en dispensación
                if ($validacion_disponibles && $prod_dispensacion['Id_Producto_Dispensacion']) {

                    // Obtener información del inventario para el lote seleccionado
                    $inventario = new complex("Inventario_Nuevo", "Id_Inventario_Nuevo", $validacion_disponibles['Id_Inventario_Nuevo']);
                    $inventario = $inventario->getData();
                    
                    // Crear objeto para actualizar el producto de dispensación
                    $oItem = new complex("Producto_Dispensacion", "Id_Producto_Dispensacion", $p['Id_Producto_Dispensacion']);

                    // Actualizar cantidad entregada (sumando la nueva entrega a lo existente)
                    $oItem->Cantidad_Entregada = $prod_dispensacion['Cantidad_Entregada'] + $p['Cantidad_Entregada'];
                    
                    // Asignar lote del inventario disponible
                    $oItem->Lote = $validacion_disponibles['Lote'];
                    
                    // Asignar ID del inventario utilizado
                    $oItem->Id_Inventario_Nuevo = $validacion_disponibles['Id_Inventario_Nuevo'];
                    
                    // Asignar código CUM del producto
                    $oItem->Cum = $p['Codigo_Cum'];
                    
                    // Asignar ID del producto
                    $oItem->Id_Producto = $p['Id_Producto'];
                    
                    // Formatear y asignar costo del producto
                    $oItem->Costo = number_format($p['Costo'], 2, ".", "");
                    
                    // Crear JSON con información de lotes seleccionados que fueron entregados
                    $oItem->Id_Inventario_Nuevo_Seleccionados = armarJsonEntregados($p, $prod_dispensacion, $p['Seleccionados']);

                    // Para cada lote seleccionado, descontar las cantidades del inventario
                    foreach ($p['Seleccionados'] as $lote) {
                        descontarCantidadesInventario($lote);
                    }
                        
                    // Guardar acta de entrega
                    SaveActa($p['Id_Dispensacion']);
                    
                    // Registrar actividad en Positiva y guardar referencia
                    $eventos_positiva[] = GuardarActividad($p);
                    
                    // Registrar cambio de producto en el sistema
                    RegistarCambioProducto($p);
                    
                    // Guardar todos los cambios del producto de dispensación
                    $oItem->save();
                    
                    // Liberar memoria de los objetos
                    unset($oItem);
                    unset($inventario);
                    
                } else {
                    // Si no hay disponibilidad, agregar a lista de productos no entregados
                    $productos_no_entregados[] = $p;
                }
            } 
            
            // CASO 2: Productos SIN selección específica de lotes
            else if (validarEntregaProducto($p["Cantidad_Entregada"], $p['Id_Inventario_Nuevo'], obtenerCantidadDisponibleLocal($p))) {
                
                // Obtener información actual del producto en dispensación
                $prod_disp = GetProducto($p);
                
                // Si el producto existe en dispensación
                if ($prod_disp['Id_Producto_Dispensacion']) {
                    
                    // Determinar ID de producto correcto (usar antiguo si existe, sino el nuevo)
                    $p['Id_Producto'] = isset($p['Id_Producto_Antiguo']) ? $p['Id_Producto'] : $p['Id_Producto'];
                    $id_producto_pendiente = null;
                    
                    // Crear objeto para actualizar el producto de dispensación
                    $pd = new complex("Producto_Dispensacion", "Id_Producto_Dispensacion", $p['Id_Producto_Dispensacion']);
                    
                    // Actualizar cantidad entregada (sumando la nueva entrega a lo existente)
                    $pd->Cantidad_Entregada = $prod_disp['Cantidad_Entregada'] + $p['Cantidad_Entregada'];
                    
                    // Actualizar lote (usar el nuevo si el actual es "Pendiente" o vacío)
                    $pd->Lote = $prod_disp['Lote'] !== 'Pendiente' || $prod_disp['Lote'] !== '' ? $p['Lote'] : $prod_disp['Lote'];
                    
                    // Actualizar ID de inventario (usar el existente si es válido, sino el nuevo)
                    $pd->Id_Inventario_Nuevo = ($prod_disp['Id_Inventario_Nuevo'] !== '' && $prod_disp['Id_Inventario_Nuevo'] != '0') ? $prod_disp['Id_Inventario_Nuevo'] : $p['Id_Inventario_Nuevo'];
                    
                    // Crear JSON con información de inventarios entregados
                    $pd->Id_Inventario_Nuevo_Seleccionados = armarJsonEntregados($p, $prod_disp);
                    
                    // Asignar código CUM del producto
                    $pd->Cum = $p['Codigo_Cum'];
                    
                    // Asignar ID del producto
                    $pd->Id_Producto = $p['Id_Producto'];
                    
                    // Formatear y asignar costo del producto
                    $pd->Costo = number_format($p['Costo'], 2, ".", "");
                    
                    // Guardar cambios del producto de dispensación
                    $pd->save();
                    
                    // Obtener ID del producto guardado
                    $id_producto_pendiente = $pd->getId();
                    
                    // Liberar memoria
                    unset($pd);

                    // Crear registro de producto pendiente para cantidades no entregadas
                    $oItem = new complex('Producto_Dispensacion_Pendiente', "Id_Producto_Dispensacion_Pendiente");
                    
                    // Calcular cantidad pendiente por entregar
                    $cantidad_pendiente = $p["Cantidad_Formulada"] - $p["Cantidad_Entregada"];
                    
                    // Asignar ID del producto de dispensación relacionado
                    $oItem->Id_Producto_Dispensacion = $id_producto_pendiente;
                    
                    // Registrar cantidad entregada en este movimiento
                    $oItem->Cantidad_Entregada = $p["Cantidad_Entregada"];
                    
                    // Registrar cantidad que queda pendiente
                    $oItem->Cantidad_Pendiente = $cantidad_pendiente;
                    
                    // Indicar cantidad faltante por entregar
                    $oItem->Entregar_Faltante = $cantidad_pendiente;
                    
                    // Guardar registro de producto pendiente
                    $oItem->save();
                    
                    // Liberar memoria
                    unset($oItem);

                    // Si tiene inventario asociado, descontar cantidades
                    if ($p["Id_Inventario_Nuevo"] != "0") {
                        descontarCantidadesInventario($p);
                    }
                    
                    // Guardar acta de entrega
                    SaveActa($p['Id_Dispensacion']);
                    
                    // Registrar actividad en Positiva y guardar referencia
                    $eventos_positiva[] = GuardarActividad($p);
                    
                    // Registrar cambio de producto en el sistema
                    RegistarCambioProducto($p);
                }
            } else {
                // Si la validación de entrega falla, agregar a no entregados
                $productos_no_entregados[] = $p;
            }
        } else {
            // Si está facturada, agregar directamente a no entregados
            $productos_no_entregados[] = $p;
        }

        // Si el producto tiene relación con Mipres, actualizar la información
        if (isset($p['Id_Producto_Dispensacion_Mipres']) && $p['Id_Producto_Dispensacion_Mipres'] != '') {
            updateProductoDispensacionMipres($p['Id_Producto_Dispensacion_Mipres'], $p['Id_Producto']);
        }
        
        // Actualizar cantidades pendientes en el sistema
        DescontarPendientes($p['Id_Dispensacion'], 0);
    }
}

// Función que descuenta cantidades del inventario al entregar un producto
function descontarCantidadesInventario($p)
{
    // revisa que el inventario no sea 0
    if ($p["Id_Inventario_Nuevo"] != "0") {
        // Convertir el Id de inventario a entero
        $p['Id_Inventario_Nuevo'] = (int) $p['Id_Inventario_Nuevo'];

        // buscar datos del inventario    
        $oItem = new complex('Inventario_Nuevo', "Id_Inventario_Nuevo", $p['Id_Inventario_Nuevo']);
        $inv_act = $oItem->getData(); // Trae los datos actuales del inventario (cantidad disponible, etc.)

        // se añadió esta validación para asegurar que existan datos antes de operar
        if ($inv_act) {
            // Cantidad actual disponible en inventario
            $cantidad = floatval($inv_act["Cantidad"]);// se usa floatval en lugar de number_format para trabajar con enteros directamente y evitar strings

            // Cantidad que se quiere entregar
            $cantidad_entregada = floatval($p["Cantidad_Entregada"]);// mismo cambio aquí, para mantener consistencia en el tipo de dato

            // optiene nueva cantidad restando lo entregado
            $cantidad_total = $cantidad - $cantidad_entregada;

            // Si la resta deja inventario en negativo, corregirlo
            if ($cantidad_total < 0) {
                $cantidad_total = 0; // No se permite negativo, queda en cero
                $p['Cantidad_Entregada'] = $cantidad; // Solo se entrega lo que había disponible
                $p['Entregar_Faltante'] = $cantidad_entregada - $cantidad; // Guardar cuánto quedó pendiente
                 // este ajuste se mantiene igual, pero se aclara que protege la integridad del inventario
            }

            // Actualizar la cantidad en el inventario
            $oItem->Cantidad = $cantidad_total;  //aquí se guarda el valor como entero, más seguro que formateado como string

            // Guardar los cambios en la base de datos
            $oItem->save();

            // Liberar el objeto de memoria
            unset($oItem);
        }
    }
}


function armarJsonEntregados($prod, $prod_ant, $array_entrega_multiple = [])
{
    $entregados = (array) json_decode($prod_ant['Id_Inventario_Nuevo_Seleccionados'], true);

    if (count($entregados) == 0) {
        if ($prod_ant['Cantidad_Entregada'] > 0) {
            $entrega_ant = array('Id_Inventario_Nuevo' => $prod_ant['Id_Inventario_Nuevo'], 'Lote' => $prod_ant['Lote'], 'Cantidad_Entregada' => $prod_ant['Cantidad_Entregada']);
            array_push($entregados, $entrega_ant);
        }
    }

    if (count($array_entrega_multiple) == 0) {
        if ($prod_ant['Cantidad_Entregada'] > 0) {
            $entrega = array('Id_Inventario_Nuevo' => $prod['Id_Inventario_Nuevo'], 'Lote' => $prod['Lote'], 'Cantidad_Entregada' => $prod['Cantidad_Entregada']);
            array_push($entregados, $entrega);
        }
    } else {

        foreach ($array_entrega_multiple as $lote) {

            array_push($entregados, $lote);
        }
    }

    return json_encode($entregados);
}

function validarDisponibilidadOtros($lotes_seleccionados, $entrega_ant)
{
    $disponibles = true;
    $max_seleccionado = (int) $entrega_ant['Cantidad_Entregada'];
    $validacion['Lote'] = $entrega_ant['Lote'];
    $validacion['Id_Inventario_Nuevo'] = $entrega_ant['Id_Inventario_Nuevo'];
    foreach ($lotes_seleccionados as $s) {
        $idInvSel = isset($s['Id_Inventario_Nuevo']) ? (int) $s['Id_Inventario_Nuevo'] : 0;
        $cantLocal = obtenerCantidadDisponibleLocal($s);
        if (!validarEntregaProducto($s['Cantidad_Entregada'], $idInvSel, $cantLocal)) {
            $disponibles = false;
        }
        if ($s['Cantidad_Entregada'] > $max_seleccionado) {
            $validacion['Lote'] = $s['Lote'];
            $validacion['Id_Inventario_Nuevo'] = $idInvSel;
            $max_seleccionado = $s['Cantidad_Entregada'];
        }
    }

    $validacion['disponibles'] = $disponibles;
    if (!$disponibles) {
        $validacion = false;
    }

    return $validacion;
}

/**
 * Obtiene la cantidad disponible de inventario nuevo
 * @param mixed $id_inventario_nuevo
 * 
 * @return [type]
 */
function cantidadInventario($id_inventario_nuevo)
{

    // Evitar consultas inválidas cuando no llega un id numérico
    $id = (int) $id_inventario_nuevo;
    if ($id <= 0) {
        return 0;
    }

    $query = "SELECT (Cantidad-Cantidad_Apartada-Cantidad_Seleccionada) AS Cantidad FROM Inventario_Nuevo WHERE Id_Inventario_Nuevo = $id"; // se crea la query para obtener la cantidad disponible en el inventario nuevo
    $oCon = new consulta(); // se crea el objeto consulta
    $oCon->setQuery($query); // se le pasa el query al objeto de consulta
    $data = $oCon->getData(); // se ejecuta la consulta
    $cantidad = isset($data['Cantidad']) ? $data['Cantidad'] : 0; // se guarda el resultado en la variable cantidad
    unset($oCon); // se destruye el objeto consulta
    return $cantidad; // se retorna la cantidad
}

/**
 * valida que la cantidad entregada sea valida
 * @param mixed $cant_entrega
 * @param mixed $id_inventario_nuevo
 * 
 * @return bool
 */
function validarEntregaProducto($cant_entrega, $id_inventario_nuevo, $cantidad_disponible = null)
{

    $id = (int) $id_inventario_nuevo;
    if ($id <= 0 && $cantidad_disponible === null) {
        return false;
    }

    // Si tenemos una cantidad local (por ejemplo, del lote recibido), úsala como referencia
    if ($cantidad_disponible !== null) {
        return (($cantidad_disponible - $cant_entrega) >= 0);
    }

    $cantidad_inventario_nuevo = cantidadInventario($id); //
    if (($cantidad_inventario_nuevo - $cant_entrega) >= 0) {
        return true;
    }
    return false;
}

// Obtiene una cantidad disponible local desde la data del producto/lote para evitar fallos de inventario
function obtenerCantidadDisponibleLocal($p)
{
    if (isset($p['Cantidad_Disponible']) && is_numeric($p['Cantidad_Disponible'])) {
        return (float) $p['Cantidad_Disponible'];
    }
    if (isset($p['Lotes'][0]['Cantidad_Disponible']) && is_numeric($p['Lotes'][0]['Cantidad_Disponible'])) {
        return (float) $p['Lotes'][0]['Cantidad_Disponible'];
    }
    if (isset($p['Lotes'][0]['Cantidad']) && is_numeric($p['Lotes'][0]['Cantidad'])) {
        return (float) $p['Lotes'][0]['Cantidad'];
    }
    return null;
}

function GuardarActividad($dis)
{
    global $modelo, $_FILES;

    // Obtener inventario desde el producto o desde el primer lote disponible
    $idInv = null;
    if (isset($dis['Id_Inventario_Nuevo']) && $dis['Id_Inventario_Nuevo'] !== '') {
        $idInv = (int) $dis['Id_Inventario_Nuevo'];
    } elseif (isset($dis['Lotes'][0]['Id_Inventario_Nuevo'])) {
        $idInv = (int) $dis['Lotes'][0]['Id_Inventario_Nuevo'];
    }

    // Si no hay inventario válido, no se registra actividad para evitar consultas inválidas
    if (!$idInv) {
        return null;
    }

    $query = "SELECT PD.Nombre from Punto_Dispensacion PD INNER JOIN Estiba E on E.Id_Punto_Dispensacion = PD.Id_Punto_Dispensacion
                INNER JOIN Inventario_Nuevo I on I.Id_Estiba = E.Id_Estiba
                WHERE I.Id_Inventario_Nuevo = $idInv";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $punto = $oCon->getData()['Nombre'];
    unset($oCon);

    if ($dis["Cantidad_Entregada"] > 0) {
        $ActividadDis['Fecha'] = date("Y-m-d H:i:s");
        $ActividadDis["Id_Dispensacion"] = $dis['Id_Dispensacion'];
        $ActividadDis["Identificacion_Funcionario"] = $modelo['Identificacion_Funcionario'];
        $ActividadDis["Detalle"] = "Se entrego la dispensacion pendiente. Producto: $dis[Nombre_Comercial] - Cantidad: $dis[Cantidad_Entregada] - punto: $punto";
        $ActividadDis["Estado"] = "Creado";

        $oItem = new complex("Actividades_Dispensacion", "Id_Actividades_Dispensacion");
        foreach ($ActividadDis as $index => $value) {
            $oItem->$index = $value;
        }
        $oItem->save();
        unset($oItem);
        $evento = null;
        /*
        // !SE INACTIVA LA INTEGRACION CON POSITIVA POR TERMINACION DEL CONTRATO
        $acta_entrega = ValidarActaEntrega($dis['Id_Dispensacion']);
        $evento = $acta_entrega? EnviarEventoPositiva($dis, $acta_entrega): null;
        */
        return ($evento);
    }
}
function SaveFirma($imagen)
{

    global $MY_FILE;

    list($type, $imagen) = explode(';', $imagen);
    list(, $imagen) = explode(',', $imagen);
    $imagen = base64_decode($imagen);

    $fot = "firma" . uniqid() . ".jpg";
    $archi = $MY_FILE . "IMAGENES/FIRMAS-DIS/" . $fot;
    file_put_contents($archi, $imagen);
    chmod($archi, 0644);
    return $fot;
}
function SaveActa($id_disp)
{
    global $nombre_archivo;

    if ($nombre_archivo && $nombre_archivo != '') {
        $oItem = new complex('Dispensacion', 'Id_Dispensacion', $id_disp);
        $oItem->Acta_Entrega = $nombre_archivo;
        $oItem->save();
        unset($oItem);
    }
}

function DescontarPendientes($dis, $cantidad)
{

    $query = "UPDATE Dispensacion D INNER JOIN Producto_Dispensacion PD ON PD.Id_Dispensacion = D.Id_Dispensacion
    SET D.Pendientes = (SELECT SUM(PD2.Cantidad_Formulada - PD2.Cantidad_Entregada)FROM Producto_Dispensacion PD2 Where PD2.Id_Dispensacion = D.Id_Dispensacion) WHERE D.Id_Dispensacion = $dis   ";

    $oItem = new consulta();
    $oItem->setQuery($query);
    $oItem->getData();
    unset($oItem);
}

/**
 * obtiene el producto pendiente de la dispensacion
 * 
 * @param mixed $prod
 * 
 * @return object
 */
function GetProducto($prod)
{

    global $queryObj;

    $id_producto = isset($prod['Id_Producto_Antiguo']) ? $prod['Id_Producto_Antiguo'] : $prod['Id_Producto'];
    $query = "SELECT *,(Cantidad_Formulada-Cantidad_Entregada) as Cantidad_Pendiente
                FROM Producto_Dispensacion
                WHERE Id_Dispensacion=$prod[Id_Dispensacion]
                AND Id_Producto=$id_producto
                HAVING Cantidad_Pendiente>0 ";
    $queryObj->SetQuery($query);
    $pd = $queryObj->ExecuteQuery('simple');
    return $pd;
}

function getStatus()
{

    global $productos_no_entregados;

    if (count($productos_no_entregados) > 0) {
        return 1;
    } else {
        return 2;
    }
}

function RegistarCambioProducto($p)
{

    global $modelo;

    if (isset($p['Id_Producto_Antiguo'])) {
        $oItem = new complex("Cambio_Producto_Dispensacion", "Id_Cambio_Producto_Dispensacion");
        $oItem->Id_Producto_Nuevo = $p['Id_Producto'];
        $oItem->Id_Producto_Antiguo = $p['Id_Producto_Antiguo'];
        $oItem->Id_Dispensacion = $p['Id_Dispensacion'];
        $oItem->Identificacion_Funcionario = $modelo['Identificacion_Funcionario'];
        $oItem->save();
        unset($oItem);
    }
}
function GuardarDispensacionPortalClientes($idDis)
{
    global $portalClientes;

    $response = $portalClientes->ActualizarDispensacion($idDis);
}
/**
 * valida que la dispensacion este facturada
 * 
 * @param mixed $idDis
 * 
 * @return object
 */
function ValidarDispensacionFacturacion($idDis)
{
    global $queryObj; // variable global de conexion a la base de datos
    $query = "SELECT * FROM Dispensacion D Where D.Id_Dispensacion = $idDis and D.Estado_Facturacion = 'Facturada' "; // consulta a la base de datos: trae si una dispensacion esta facturada
    $queryObj->SetQuery($query); // se ejecuta la consulta
    $productos_sin_precio = $queryObj->ExecuteQuery('simple'); // se almacena el resultado de la consulta en la variable productos_sin_precio
    return $productos_sin_precio; // se retorna la variable productos_sin_precio
}

function GetProductosSinPrecio($dispensacion, $idDis)
{
    global $queryObj;

    if (strtolower($dispensacion['Tipo_Servicio']) == "evento") {
        $exits = " AND NOT exists (SELECT Codigo_Cum FROM Producto_Evento WHERE Codigo_Cum=P.Codigo_Cum AND Nit_EPS=$dispensacion[Id_Cliente] AND Precio>0 )  ";
    } elseif (strtolower($dispensacion['Tipo_Servicio']) == 'cohortes') {
        $exits = " AND NOT exists (SELECT Id_Producto FROM Producto_Cohorte WHERE Id_Producto=PD.Id_Producto AND Nit_EPS=$dispensacion[Id_Cliente] ) ";
    }

    $query = "SELECT PD.Id_Producto,P.Nombre_Comercial,P.Codigo_Cum, IFNULL((SELECT Precio FROM Precio_Regulado WHERE Codigo_Cum=P.Codigo_Cum),0) as Precio, CONCAT(P.Principio_Activo,' ',P.Presentacion,' ',P.Concentracion,' ', P.Cantidad,' ', P.Unidad_Medida) as Nombre
    FROM Producto_Dispensacion PD
    INNER JOIN Producto P ON PD.Id_Producto=P.Id_Producto
    WHERE PD.Id_Dispensacion=$idDis " . $exits . " GROUP BY PD.Id_Producto HAVING Precio=0 ";

    $queryObj->SetQuery($query);
    $productos_sin_precio = $queryObj->ExecuteQuery('Multiple');

    return $productos_sin_precio;
}

function GetIdFactura($idDis)
{
    global $queryObj;
    $query = "SELECT Id_Factura FROM Factura WHERE Id_Dispensacion=$idDis";
    $queryObj->SetQuery($query);
    $fact = $queryObj->ExecuteQuery('simple');
    return $fact['Id_Factura'];
}

function GetTotalFactura($id)
{
    global $queryObj;
    $query = "SELECT SUM(Subtotal) as Total FROM Producto_Factura WHERE Id_Factura=$id";
    $queryObj->SetQuery($query);
    $fact = $queryObj->ExecuteQuery('simple');
    return $fact['Total'];
}

function ValidarDispensacionMipres($idDis)
{

    global $queryObj, $mipres, $reclamante;
    $codigo_sede_mp = GetCodigoSede();
    $nit_mp = GetNitProh();
    $productos = GetProductosDispensacionMipres($idDis); //Productos ya formulados 
    foreach ($productos as $prod) { //recorres lista de productos 
        $data['ID'] = (int) $prod['ID']; //captura el ID del producto 
        $data['CodSerTecEntregado'] = $prod['Cum']; //Captura el cum del producto 
        $data['CantTotEntregada'] = $prod['Entregada']; // captura la cantidad entregada 
        $data['EntTotal'] = 0;  
        $data['CausaNoEntrega'] = 0;



        // Crear objeto DateTime
        $dt = new DateTime($prod["Fecha"]);

        // Formatear fecha
        $fecha_iso = $dt->format('Y-m-d');

        // Guardar en el array de envío
        $data["FecEntrega"] = $fecha_iso;

        $data['NoLote'] = $prod["Lote"]; //captura el lote
        $data['TipoIDRecibe'] = $reclamante['Codigo']; //codigo del reclamante
        $data['NoIDRecibe'] = $reclamante['Id_Reclamante']; //id del reclamante
        $entrega = $mipres->ReportarEntrega($data); //envia todo el arreglo del producto y genera un reporte de entrega
        //var_dump($entrega);
        if ($entrega[0]['Id']) { // Si MIPRES confirma la entrega (retorna un Id válido)
            $oItem = new complex('Producto_Dispensacion_Mipres', 'Id_Producto_Dispensacion_Mipres', $prod['Id_Producto_Dispensacion_Mipres']); // Actualizar tabla Producto_Dispensacion_Mipres 
            $oItem->IdEntrega = $entrega[0]['IdEntrega'];  //actualiza con el id de entrega
            $oItem->Fecha_Entrega = date("Y-m-d H:i:s"); //actualiza con la fecha de entrega 
            $oItem->save(); // guarda en la base de datos 
            unset($oItem);  //libera variable 

            $oItem = new complex('Dispensacion_Mipres', 'Id_Dispensacion_Mipres', $prod['Id_Dispensacion_Mipres']); // actualizar tabla Dispensacion_Mipres
            $oItem->Estado = 'Entregado'; // cambia el estado de la dispensación a entregado 
            $oItem->save(); //actualiza la base de datos 
            unset($oItem); //libera variable 
        }
    }
    if (count($productos) == 0) { // sin productos programados  --  Se actualiza como radicado programado 
        $oItem = new complex('Dispensacion_Mipres', 'Id_Dispensacion_Mipres', $idDis);  //Actualizacion Tabla Dispensacion_Mipres 
        $oItem->Estado = 'Radicado Programado'; //Actualiza el estado a Radicado 
        $oItem->save(); //Actualiza base de datos 
        unset($oItem); //Libera varibale 
    }
}

function GetProductosDispensacionMipres($id_dis)
{
    global $queryObj;
    //obtener los productos de una dispensación
    $query = 'SELECT D.Fecha_Actual AS Fecha, SUM(PD.Cantidad_Formulada) AS Formulada, SUM(PD.Cantidad_Entregada) AS Entregada, PDM.ID, PD.Id_Dispensacion, PD.Cum, PD.Lote, PDM.Id_Producto_Dispensacion_Mipres, PDM.Id_Dispensacion_Mipres
    FROM Producto_Dispensacion PD
    INNER JOIN Dispensacion D ON D.Id_Dispensacion = PD.Id_Dispensacion
    INNER JOIN Producto_Dispensacion_Mipres PDM ON PDM.Id_Producto_Dispensacion_Mipres = PD.Id_Producto_Dispensacion_Mipres
    WHERE PD.Id_Dispensacion=' . $id_dis . '
    GROUP BY PD.Id_Producto_Dispensacion_Mipres
    HAVING Entregada = Formulada';
    $queryObj->SetQuery($query); //Ejecucion de consulta
    $productos = $queryObj->ExecuteQuery('Multiple');
    return $productos; // retorna productos ya entregados totalmente 
}

function GetPendientes($idDis)
{

    global $queryObj;

    $query = "SELECT PD.Id_Dispensacion,PD.Id_Producto,(SELECT Codigo FROM Dispensacion WHERE Id_Dispensacion=PD.Id_Dispensacion) as Codigo, CONCAT(P.Principio_Activo,' ',P.Presentacion,' ',P.Concentracion,' ', P.Cantidad,' ', P.Unidad_Medida) as Nombre,P.Nombre_Comercial,P.Codigo_Cum
    FROM Producto_Dispensacion PD
    INNER JOIN Producto P ON PD.Id_Producto=P.Id_Producto
    WHERE PD.Id_Dispensacion=$idDis AND PD.Cantidad_Formulada !=PD.Cantidad_Entregada";
    $queryObj->SetQuery($query);
    $pendientes = $queryObj->ExecuteQuery('Multiple');

    return $pendientes;
}

function GetDispensacion($idDis)
{
    global $queryObj;

    $query = "SELECT Id_Dispensacion_Mipres,Id_Dispensacion,Id_Tipo_Servicio FROM Dispensacion WHERE Id_Dispensacion=$idDis";
    $queryObj->SetQuery($query);
    $dispensacion = $queryObj->ExecuteQuery('simple');
    return $dispensacion;
}

function GetProductosMipres($id)
{
    global $queryObj;
    $query = 'SELECT
    PD.*, D.Fecha_Maxima_Entrega, IFNULL(PD.IdProgramacion,0) as IdProgramacion
    FROM Producto_Dispensacion_Mipres PD INNER JOIN Dispensacion_Mipres D ON PD.Id_Dispensacion_Mipres=D.Id_dispensacion_Mipres
    WHERE
    PD.Id_Dispensacion_Mipres=' . $id;
    $queryObj->SetQuery($query);
    $productos = $queryObj->ExecuteQuery('Multiple');
    return $productos;
}

function GetCodigoSede()
{

    global $queryObj;
    $query = 'SELECT Codigo_Sede	 FROM Configuracion WHERE Id_Configuracion=1';
    $queryObj->SetQuery($query);
    $dato = $queryObj->ExecuteQuery('simple');
    return $dato['Codigo_Sede'];
}

function GetNitProh()
{

    global $queryObj;

    $query = 'SELECT NIT FROM Configuracion WHERE Id_Configuracion=1';
    $queryObj->SetQuery($query);
    $dato = $queryObj->ExecuteQuery('simple');
    $n = explode('-', $dato['NIT']);
    $nit = $n[0];
    $nit = str_replace('.', '', $nit);
    return $nit;
}

function GetLoteEntregado($idProducto, $idDis)
{

    global $queryObj;
    $query = "SELECT Lote From Producto_Dispensacion WHERE Id_Producto_Mipres=$idProducto AND Id_Dispensacion=$idDis ";
    $queryObj->SetQuery($query);
    $lote = $queryObj->ExecuteQuery('simple');
    return $lote['Lote'];
}

function updateProductoDispensacionMipres($id_producto_mipres, $id_producto)
{
    $oItem = new complex('Producto_Dispensacion_Mipres', 'Id_Producto_Dispensacion_Mipres', $id_producto_mipres);// Cargar el registro de la tabla Producto_Dispensacion_Mipres usando su id
    $oItem->Id_Producto = $id_producto;// Cambiar el valor del campo Id_Producto
    $oItem->save(); // Guardar el cambio en la base de datos
    unset($oItem); // Liberar el objeto de memoria
}

function GuardarDatosReclamante($reclamante)
{

    global $queryObj;

    if ($reclamante['Id_Reclamante']) {
        $query = "SELECT * FROM Reclamante WHERE Id_Reclamante=$reclamante[Id_Reclamante]";
        $queryObj->SetQuery($query);
        $usuario = $queryObj->ExecuteQuery('simple');
    } else {

        $usuario = true;
    }
    $oItem = new complex('Reclamante', 'Id_Reclamante', (!$usuario) ? 0 : $usuario['Id_Reclamante']);

    $oItem->Nombre = $reclamante['Nombre'];

    $oItem->Id_Reclamante = $reclamante['Id_Reclamante'];

    $oItem->Tipo_Doc = $reclamante['Codigo'];

    $oItem->save();

    unset($oItem);

    addReclamanteToDispensacion($reclamante);
}

/**
 * Se crea funcion para asociar reclamante a la dispensacion actual, esta relacion se guarda en la tabla dispensacion_reclamante
 */

function addReclamanteToDispensacion($reclamante)
{
    global $modelo, $queryObj;

    $parentesco = $reclamante['parentesco'];

    $Id_Reclamante = $reclamante['Id_Reclamante'];

    $Id_Dispensacion = GetDispensacion($modelo['Id_Dispensacion'])['Id_Dispensacion'];

    if ($Id_Reclamante != '' && $Id_Dispensacion != '') {

        $query = "SELECT * FROM Dispensacion_Reclamante WHERE Dispensacion_Id = '$Id_Dispensacion' ";
        $queryObj->SetQuery($query);
        $reclamante = $queryObj->ExecuteQuery('simple');

        $oItem = new complex('Dispensacion_Reclamante', 'Id', ($reclamante == null || $reclamante == 'null') ? 0 : $reclamante['Id']);

        $oItem->Reclamante_Id = $Id_Reclamante;

        $oItem->Dispensacion_Id = $Id_Dispensacion;

        $oItem->Parentesco = $parentesco;

        $oItem->save();

        unset($oItem);

        return;
    }
}
function ValidarActaEntrega($id_dis)
{
    $oItem = new complex('Dispensacion', 'Id_Dispensacion', $id_dis);
    $dis = $oItem->getData();

    if ($dis) {
        $ruta = $dis['Acta_Entrega'];
        $row['name'] = pathinfo($ruta, PATHINFO_BASENAME);
        $row['type'] = "application/" . pathinfo($ruta, PATHINFO_EXTENSION);
        $row['tmp_name'] = $ruta;
        return $row;
    }
}

function EnviarEventoPositiva($producto, $soporte)
{
    global $queryObj, $modelo;

    $query = "SELECT PD.numeroAutorizacion
                from Positiva_Data PD
                    Where PD.Id_Dispensacion = $producto[Id_Dispensacion] ";


    $queryObj->SetQuery($query);
    $autorizacion = $queryObj->ExecuteQuery('simple');

    $acta_entrega['files'] = $soporte;
    if ($autorizacion['numeroAutorizacion'] && $acta_entrega['files']['name']) {
        $positiva = new Fase2($autorizacion['numeroAutorizacion'], $acta_entrega, $producto['Cantidad_Entregada'], null, 'Entrega exitosa', 'SE', $modelo['Identificacion_Funcionario']);
        $rta = $positiva->Enviar();
    }
    return $rta;
}
