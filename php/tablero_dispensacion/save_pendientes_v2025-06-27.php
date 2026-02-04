<?php

error_reporting(E_ALL);
ini_set("display_errors", "On");

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once '../../class/class.querybasedatos.php';
include_once '../../class/class.http_response.php';
require_once '../../class/class.configuracion.php';
include_once '../../class/class.portal_clientes.php';
include_once '../../class/class.facturaccionmasiva.php';
// include_once '../../class/class.integracion_positiva.php';
include_once '../../helper/response.php';
include_once '../../class/class.mipres.php';
require '../../class/class.awsS3.php';



$queryObj = new QueryBaseDatos();
$response = array();
$http_response = new HttpResponse();

$configuracion = new Configuracion();
$portalClientes = new PortalCliente($queryObj);
$facturaccion = new Facturacion_Masiva();
// $mipres = new Mipres();

$id_disp = '';

date_default_timezone_set('America/Bogota');

$modelo = (isset($_REQUEST['modelo']) ? $_REQUEST['modelo'] : '');
$productos = (isset($_REQUEST['productos']) ? $_REQUEST['productos'] : '');
$reclamante = (isset($_REQUEST['reclamante']) ? $_REQUEST['reclamante'] : '');
$modelo = mb_convert_encoding($modelo, 'UTF-8');
$modelo = (array) json_decode($modelo);
$productos = (array) json_decode(mb_convert_encoding($productos, "UTF-8"), true);
$reclamante = (array) json_decode(mb_convert_encoding($reclamante, 'UTF-8'), true);

$productos_no_entregados = [];
$eventos_positiva = [];
$imagen = $modelo["Firma_Reclamante"];
$fot = '';

if ($imagen != '') {
    $fot = SaveFirma($imagen);
    $oItem = new complex('Dispensacion', 'Id_Dispensacion', $modelo['Id_Dispensacion']);
    $oItem->Firma_Reclamante = $fot;
    $oItem->save();
    unset($oItem);
}

$modelo["Firma_Reclamante"] = $fot;
try {
    if (!empty($_FILES['acta_entrega']['name'])) {
        $s3 = new AwsS3();
        $ruta = "dispensacion/auditoria/soportes/acta_entrega";
        $nombre_archivo = $s3->putObject( $ruta, $_FILES['acta_entrega']);
    }
} catch (\Throwable $th) {
    http_response_code(500);
    echo $th->getMessage();
    exit;
}

$idFactura = 0;


try {
    if($modelo['Identificacion_Funcionario'] != '1099375816'){
        
        SaveProductosDispensacion($productos);
    }
} catch (\Throwable $th) {
    http_response_code(500);
    echo $th->getMessage();
    exit;
}
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
$response['eventos_positiva'] = $eventos_positiva;


//GuardarDispensacionPortalClientes($modelo['Id_Dispensacion']);

ValidarDispensacionMipres($modelo['Id_Dispensacion']);
$response['id_factura'] = $idFactura;

echo json_encode($response);


function SaveProductosDispensacion($prod)
{
    global $productos_no_entregados, $eventos_positiva;

    foreach ($prod as $p) {
        $facturada = ValidarDispensacionFacturacion($p['Id_Dispensacion']);
        if (!$facturada) {
            if (isset($p['Seleccionados'])) {
                $prod_dispensacion = GetProducto($p);
                $validacion_disponibles = validarDisponibilidadOtros($p['Seleccionados'], $prod_dispensacion);
                if ($validacion_disponibles && $prod_dispensacion['Id_Producto_Dispensacion']) {

                    $inventario = new complex("Inventario_Nuevo", "Id_Inventario_Nuevo", $validacion_disponibles['Id_Inventario_Nuevo']);
                    $inventario = $inventario->getData();
                    $oItem = new complex("Producto_Dispensacion", "Id_Producto_Dispensacion", $p['Id_Producto_Dispensacion']);

                    $oItem->Cantidad_Entregada = $prod_dispensacion['Cantidad_Entregada'] + $p['Cantidad_Entregada'];
                    $oItem->Lote = $validacion_disponibles['Lote'];
                    // $oItem->Fecha_Vencimiento = $inventario['Fecha_Vencimiento'];
                    $oItem->Id_Inventario_Nuevo = $validacion_disponibles['Id_Inventario_Nuevo'];
                    $oItem->Cum = $p['Codigo_Cum'];
                    $oItem->Id_Producto = $p['Id_Producto'];
                    $oItem->Costo = number_format($p['Costo'], 2, ".", "");

                    $oItem->Id_Inventario_Nuevo_Seleccionados = armarJsonEntregados($p, $prod_dispensacion, $p['Seleccionados']);

                    foreach ($p['Seleccionados'] as $lote) {

                        descontarCantidadesInventario($lote);
                    }
                    SaveActa($p['Id_Dispensacion']);
                    $eventos_positiva[] = GuardarActividad($p);
                    RegistarCambioProducto($p);
                    $oItem->save();
                    unset($oItem);
                    unset($inventario);
                } else {
                    $productos_no_entregados[] = $p;
                }
            } else if (validarEntregaProducto($p["Cantidad_Entregada"], $p['Id_Inventario_Nuevo'])) {
                $prod_disp = GetProducto($p);

                if ($prod_disp['Id_Producto_Dispensacion']) {

                    $p['Id_Producto'] = isset($p['Id_Producto_Antiguo']) ? $p['Id_Producto'] : $p['Id_Producto'];
                    $id_producto_pendiente = null;
                    $pd = new complex("Producto_Dispensacion", "Id_Producto_Dispensacion", $p['Id_Producto_Dispensacion']);
                    $pd->Cantidad_Entregada = $prod_disp['Cantidad_Entregada'] + $p['Cantidad_Entregada'];
                    $pd->Lote = $prod_disp['Lote'] !== 'Pendiente' || $prod_disp['Lote'] !== '' ? $p['Lote'] : $prod_disp['Lote'];
                    // $pd->Fecha_Vencimiento = $prod_disp['Fecha_Vencimiento'] !== '' ? $p['Fecha_Vencimiento'] : $prod_disp['Fecha_Vencimiento'];
                    $pd->Id_Inventario_Nuevo = ($prod_disp['Id_Inventario_Nuevo'] !== '' && $prod_disp['Id_Inventario_Nuevo'] != '0') ? $prod_disp['Id_Inventario_Nuevo'] : $p['Id_Inventario_Nuevo'];
                    $pd->Id_Inventario_Nuevo_Seleccionados = armarJsonEntregados($p, $prod_disp);
                    $pd->Cum = $p['Codigo_Cum'];
                    $pd->Id_Producto = $p['Id_Producto'];
                    $pd->Costo = number_format($p['Costo'], 2, ".", "");
                    $pd->save();
                    $id_producto_pendiente = $pd->getId();
                    unset($pd);

                    $oItem = new complex('Producto_Dispensacion_Pendiente', "Id_Producto_Dispensacion_Pendiente");
                    $cantidad_pendiente = $p["Cantidad_Formulada"] - $p["Cantidad_Entregada"];
                    $oItem->Id_Producto_Dispensacion = $id_producto_pendiente;
                    $oItem->Cantidad_Entregada = $p["Cantidad_Entregada"];
                    $oItem->Cantidad_Pendiente = $cantidad_pendiente;
                    $oItem->Entregar_Faltante = $cantidad_pendiente;

                    $oItem->save();
                    unset($oItem);

                    if ($p["Id_Inventario_Nuevo"] != "0") {
                        descontarCantidadesInventario($p);
                    }
                    SaveActa($p['Id_Dispensacion']);
                    $eventos_positiva[] = GuardarActividad($p);
                    RegistarCambioProducto($p);
                }
            } else {
                $productos_no_entregados[] = $p;
            }
        } else {
            $productos_no_entregados[] = $p;
        }

        if (isset($p['Id_Producto_Dispensacion_Mipres']) && $p['Id_Producto_Dispensacion_Mipres'] != '') {
            updateProductoDispensacionMipres($p['Id_Producto_Dispensacion_Mipres'], $p['Id_Producto']);
        }
        DescontarPendientes($p['Id_Dispensacion'], 0);
    }
}

function descontarCantidadesInventario($p)
{

    if ($p["Id_Inventario_Nuevo"] != "0") {
        $p['Id_Inventario_Nuevo'] = (int) $p['Id_Inventario_Nuevo'];
        $oItem = new complex('Inventario_Nuevo', "Id_Inventario_Nuevo", $p['Id_Inventario_Nuevo']);
        $inv_act = $oItem->getData();
        $cantidad = number_format((int) $inv_act["Cantidad"], 0, "", "");
        $cantidad_entregada = number_format($p["Cantidad_Entregada"], 0, "", "");
        $cantidad_total = $cantidad - $cantidad_entregada;
        if ($cantidad_total < 0) {
            $cantidad_total = 0;
            $p['Cantidad_Entregada'] = $cantidad;
            $p['Entregar_Faltante'] = $cantidad_entregada - $cantidad;
        }
        $oItem->Cantidad = number_format($cantidad_total, 0, "", "");
        $oItem->save();
        unset($oItem);
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
        if (!validarEntregaProducto($s['Cantidad_Entregada'], $s['Id_Inventario_Nuevo'])) {
            $disponibles = false;
        }
        if ($s['Cantidad_Entregada'] > $max_seleccionado) {
            $validacion['Lote'] = $s['Lote'];
            $validacion['Id_Inventario_Nuevo'] = $s['Id_Inventario_Nuevo'];
            $max_seleccionado = $s['Cantidad_Entregada'];
        }
    }

    $validacion['disponibles'] = $disponibles;
    if (!$disponibles) {
        $validacion = false;
    }

    return $validacion;
}

function cantidadInventario($id_inventario_nuevo)
{

    $query = "SELECT (Cantidad-Cantidad_Apartada-Cantidad_Seleccionada) AS Cantidad FROM Inventario_Nuevo WHERE Id_Inventario_Nuevo = $id_inventario_nuevo";
    $oCon = new consulta();
    $oCon->setQuery($query);
    $cantidad = $oCon->getData()['Cantidad'];
    unset($oCon);
    return $cantidad;
}

function validarEntregaProducto($cant_entrega, $id_inventario_nuevo)
{

    $cantidad_inventario_nuevo = cantidadInventario($id_inventario_nuevo);
    if (($cantidad_inventario_nuevo - $cant_entrega) >= 0) {
        return true;
    }
    return false;
}

function GuardarActividad($dis)
{
    global $modelo, $_FILES;

    $query = "SELECT PD.Nombre from Punto_Dispensacion PD INNER JOIN Estiba E on E.Id_Punto_Dispensacion = PD.Id_Punto_Dispensacion
                INNER JOIN Inventario_Nuevo I on I.Id_Estiba = E.Id_Estiba
                WHERE I.Id_Inventario_Nuevo = $dis[Id_Inventario_Nuevo]";

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
function ValidarDispensacionFacturacion($idDis)
{
    global $queryObj;
    $query = "SELECT * FROM Dispensacion D Where D.Id_Dispensacion = $idDis and D.Estado_Facturacion = 'Facturada' ";
    $queryObj->SetQuery($query);
    $productos_sin_precio = $queryObj->ExecuteQuery('simple');

    return $productos_sin_precio;
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
    $productos = GetProductosDispensacionMipres($idDis);

    foreach ($productos as $prod) {
        $data['ID'] = (int) $prod['ID'];
        $data['CodSerTecEntregado'] = $prod['Cum'];
        $data['CantTotEntregada'] = $prod['Entregada'];
        $data['EntTotal'] = 0;
        $data['CausaNoEntrega'] = 0;
        $data['FecEntrega'] = $prod["Fecha"];
        $data['NoLote'] = $prod["Lote"];
        $data['TipoIDRecibe'] = $reclamante['Codigo'];
        $data['NoIDRecibe'] = $reclamante['Id_Reclamante'];
        $entrega = $mipres->ReportarEntrega($data);
        if ($entrega[0]['Id']) {
            $oItem = new complex('Producto_Dispensacion_Mipres', 'Id_Producto_Dispensacion_Mipres', $prod['Id_Producto_Dispensacion_Mipres']);
            $oItem->IdEntrega = $entrega[0]['IdEntrega'];
            $oItem->Fecha_Entrega = date("Y-m-d H:i:s");
            $oItem->save();
            unset($oItem);

            $oItem = new complex('Dispensacion_Mipres', 'Id_Dispensacion_Mipres', $prod['Id_Dispensacion_Mipres']);
            $oItem->Estado = 'Entregado';
            $oItem->save();
            unset($oItem);
        }
    }
    if (count($productos) == 0) {
        $oItem = new complex('Dispensacion_Mipres', 'Id_Dispensacion_Mipres', $idDis);
        $oItem->Estado = 'Radicado Programado';
        $oItem->save();
        unset($oItem);
    }
}

function GetProductosDispensacionMipres($id_dis)
{
    global $queryObj;
    $query = 'SELECT D.Fecha_Actual AS Fecha, SUM(PD.Cantidad_Formulada) AS Formulada, SUM(PD.Cantidad_Entregada) AS Entregada, PDM.ID, PD.Id_Dispensacion, PD.Cum, PD.Lote, PDM.Id_Producto_Dispensacion_Mipres, PDM.Id_Dispensacion_Mipres
    FROM Producto_Dispensacion PD
    INNER JOIN Dispensacion D ON D.Id_Dispensacion = PD.Id_Dispensacion
    INNER JOIN Producto_Dispensacion_Mipres PDM ON PDM.Id_Producto_Dispensacion_Mipres = PD.Id_Producto_Dispensacion_Mipres
    WHERE PD.Id_Dispensacion=' . $id_dis . '
    GROUP BY PD.Id_Producto_Dispensacion_Mipres
    HAVING Entregada = Formulada';
    $queryObj->SetQuery($query);
    $productos = $queryObj->ExecuteQuery('Multiple');
    return $productos;
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
    $oItem = new complex('Producto_Dispensacion_Mipres', 'Id_Producto_Dispensacion_Mipres', $id_producto_mipres);
    $oItem->Id_Producto = $id_producto;
    $oItem->save();
    unset($oItem);
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

    if($dis){
        $ruta= $dis['Acta_Entrega'];
        $row['name'] = pathinfo($ruta, PATHINFO_BASENAME);
        $row['type'] = "application/".pathinfo($ruta, PATHINFO_EXTENSION);
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

    $acta_entrega['files']=$soporte;
    if($autorizacion['numeroAutorizacion'] && $acta_entrega['files']['name']){
        $positiva = new Fase2($autorizacion['numeroAutorizacion'], $acta_entrega, $producto['Cantidad_Entregada'], null, 'Entrega exitosa', 'SE', $modelo['Identificacion_Funcionario']);
        $rta = $positiva->Enviar();
    }
    return $rta;
}
