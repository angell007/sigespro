<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once '../../config/start.inc.php';
include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';
include_once '../../class/class.contabilizar.php';
require_once '../../class/class.configuracion.php';
require_once '../../class/class.qr.php'; /* AGREGAR ESTA CLASE PARA GENERAR QR */

$contabilizar = new Contabilizar();
$configuracion = new Configuracion();
$datos = (isset($_REQUEST['datos']) ? $_REQUEST['datos'] : '');
$productoCompra = (isset($_REQUEST['productos']) ? $_REQUEST['productos'] : '');
$codigoCompra = (isset($_REQUEST['codigoCompra']) ? $_REQUEST['codigoCompra'] : '');
$tipoCompra = (isset($_REQUEST['tipoCompra']) ? $_REQUEST['tipoCompra'] : '');
$facturas = (isset($_REQUEST['facturas']) ? $_REQUEST['facturas'] : '');
$comparar = (isset($_REQUEST['comparar']) ? $_REQUEST['comparar'] : '');
$archivos = (isset($_REQUEST['archivos']) ? $_REQUEST['archivos'] : '');
$no_conforme_devolucion = (isset($_REQUEST['id_no_conforme']) ? $_REQUEST['id_no_conforme'] : false);

$datosProductos = (array) json_decode($productoCompra, true);
$datos = (array) json_decode($datos);
$facturas = (array) json_decode($facturas, true);
$comparar = (array) json_decode($comparar, true);

$datos_movimiento_contable = array();

$prov_ret = GetInfoRetencionesProveedor($datos['Id_Proveedor']);
$columns = array_column($facturas, 'Retenciones');

if ($prov_ret['Tipo_Retencion'] == 'Permanente' || $prov_ret['Tipo_Reteica'] == 'Permanente') {
    if ($prov_ret['Id_Plan_Cuenta_Retefuente'] != '' || $prov_ret['Id_Plan_Cuenta_Reteica'] != '') {
        if (count($columns) == 0) {
            $resultado['mensaje'] = "Ha ocurrido un incoveniente con las retenciones de las facturas cargadas, contacte con el administrador del sistema!";
            $resultado['tipo'] = "error";

            echo json_encode($resultado);
            exit;
        }
    }
}

$cod = $configuracion->getConsecutivo('Acta_Recepcion', 'Acta_Recepcion');
$datos['Codigo'] = $cod;

$estado = ValidarCodigo($cod);
if ($estado) {
    $cod = $configuracion->getConsecutivo('Acta_Recepcion', 'Acta_Recepcion');
    $datos['Codigo'] = $cod;
}

$oItem = new complex("Acta_Recepcion", "Id_Acta_Recepcion");
foreach ($datos as $index => $value) {
    $oItem->$index = $value;
}
if ($datos['Tipo'] == 'Nacional') {
    $oItem->Id_Orden_Compra_Nacional = $datos['Id_Orden_Compra'];
} else {
    $oItem->Id_Orden_Compra_Internacional = $datos['Id_Orden_Compra'];
}

$oItem->save();
$id_Acta_Recepcion = $oItem->getId();
unset($oItem);
/* AQUI GENERA QR */
$qr = generarqr('actarecepcion', $id_Acta_Recepcion, '/IMAGENES/QR/');
$oItem = new complex("Acta_Recepcion", "Id_Acta_Recepcion", $id_Acta_Recepcion);
$oItem->Codigo_Qr = $qr;
$oItem->save();
unset($oItem);
/* HASTA AQUI GENERA QR */

// productos
$oCon = new consulta();

switch ($tipoCompra) {

    case "Nacional": {
            $query = "SELECT	Id_Orden_Compra_Nacional as id,Codigo,Identificacion_Funcionario,Id_Bodega_Nuevo,Id_Proveedor FROM Orden_Compra_Nacional WHERE Codigo = '" . $codigoCompra . "'";
            $oCon->setQuery($query);
            $detalleCompra = $oCon->getData();
            $oCon->setTipo('Multiple');
            unset($oCon);
            break;
        }
    case "Internacional": {

            $query = "SELECT Id_Orden_Compra_Internacional as id,Codigo,Identificacion_Funcionario,Id_Bodega_Nuevo,Id_Proveedor FROM Orden_Compra_Internacional WHERE Codigo = '" . $codigoCompra . "'";
            $oCon->setQuery($query);
            $detalleCompra = $oCon->getData();
            $oCon->setTipo('Multiple');
            unset($oCon);
            break;
        }
}

/* var_dump($detalleCompra);
exit; */

// realizar guardado para las caracteristicas de los productos
//1. revisar cuales fueron marcados y no marcados en el array que traigo.
$i = -1;
$contador = 0;
$genero_no_conforme = false;
$id_no_conforme = '';
$productos = explode(",", $comparar['Id_Producto']);

$id_productos_acta_orden= array_column($datosProductos, "Id_Producto_Orden_Compra");
$ids_oc = implode(',', $id_productos_acta_orden);
$query = "SELECT GROUP_CONCAT(Id_Producto)as Id_Solicitados from Producto_Orden_Compra_$tipoCompra Where Id_Producto_Orden_Compra_$tipoCompra in($ids_oc)"; 
$oCon = new consulta();
$oCon->setQuery($query);
$id_productos_acta_orden = $oCon->getData()['Id_Solicitados'];
$id_productos_acta_orden = explode(',', $id_productos_acta_orden);
$id_productos_acta = array_column($datosProductos, "Id_Producto");
$id_productos_acta = array_merge($id_productos_acta, $id_productos_acta_orden);

foreach ($productos as $value) {

    if (!array_search($value, $id_productos_acta) && array_search($value, $id_productos_acta) !== 0) {
        if (!$genero_no_conforme) {
            $genero_no_conforme = true;
            $configuracion = new Configuracion();
            $cod = $configuracion->getConsecutivo('No_Conforme', 'No_Conforme');
            $oItem2 = new complex('No_Conforme', 'Id_No_Conforme');
            $oItem2->Codigo = $cod;
            $oItem2->Persona_Reporta = $datos['Identificacion_Funcionario'];
            $oItem2->Tipo = "Compra";
            $oItem2->Id_Acta_Recepcion_Compra = $id_Acta_Recepcion;
            $oItem2->save();
            $id_no_conforme = $oItem2->getId();
            unset($oItem2);

            /*AQUI GENERA QR */
            $qr = generarqr('noconforme', $id_no_conforme, '/IMAGENES/QR/');
            $oItem = new complex("No_Conforme", "Id_No_Conforme", $id_no_conforme);
            $oItem->Codigo_Qr = $qr;
            $oItem->save();
            unset($oItem);
            /* HASTA AQUI GENERA QR */
        }
        $query = "SELECT	Cantidad
            FROM Producto_Orden_Compra_Nacional
            WHERE Id_Producto = $value 
            AND Id_Orden_Compra_Nacional= $comparar[Id_Orden_Nacional]";

        $oCon = new consulta();
        $oCon->setQuery($query);
        $cantidad = $oCon->getData();
        unset($oCon);

        $oItem2 = new complex('Producto_No_Conforme', 'Id_Producto_No_Conforme');
        $oItem2->Id_Producto = $value;
        $oItem2->Id_No_Conforme = $id_no_conforme;
        $oItem2->Id_Compra = $datos["Id_Orden_Compra"];
        $oItem2->Tipo_Compra = $datos["Tipo"];
        $oItem2->Id_Acta_Recepcion = $id_Acta_Recepcion;
        $oItem2->Cantidad = number_format($cantidad['Cantidad'], 0, "", "");
        $oItem2->Id_Causal_No_Conforme = 2;
        $oItem2->Observaciones = "PRODUCTO NO LLEGO EN FISICO";
        $oItem2->save();
        unset($oItem2);
    }
}

$productosRecibidos = [];
foreach ($datosProductos as $prod) {
    unset($prod["producto"][count($prod["producto"]) - 1]);
    foreach ($prod["producto"] as $item) {
        $i++;

        if ($item['Lote'] != '') {

            $productosRecibidos[$item['Id_Producto']] =array('Cantidad'=> $productosRecibidos[$item['Id_Producto']] ? $productosRecibidos[$item['Id_Producto']]['Cantidad'] + $item['Cantidad'] +  $item["Cantidad_No_Conforme"] : $item['Cantidad']+  $item["Cantidad_No_Conforme"], 
        'Id_Producto_Orden_Compra' => $prod["Id_Producto_Orden_Compra"]
        );

            $oItem = new complex('Producto_Acta_Recepcion', 'Id_Producto_Acta_Recepcion');
            //mandar productos a Producto_Acta_Recepcion

            foreach ($item as $index => $value) {

                if ($index == 'Temperatura') {
                    $oItem->$index = number_format($value, 2, ".", "");
                } else {
                    $oItem->$index = $value;
                }
            }
            $subtotal = ((int) $item['Cantidad']) * ((int) $item['Precio']);
            $oItem->Id_Producto_Orden_Compra = $prod["Id_Producto_Orden_Compra"];
            $oItem->Codigo_Compra = $codigoCompra;
            $oItem->Tipo_Compra = $tipoCompra;
            $oItem->Id_Acta_Recepcion = $id_Acta_Recepcion;
            // $precio = number_format($prod['Precio'],2,".","");
            $subtotal = number_format((int) $subtotal, 2, ".", "");
            $oItem->Subtotal = round($subtotal);
            $oItem->save();
            unset($oItem);

            if ($item["No_Conforme"] != "") {
                if (!$genero_no_conforme) { // Para que solo registre un solo registro por cada no conforme de productos.
                    $genero_no_conforme = true;
                    $configuracion = new Configuracion();
                    $cod = $configuracion->getConsecutivo('No_Conforme', 'No_Conforme');
                    $oItem2 = new complex('No_Conforme', 'Id_No_Conforme');
                    $oItem2->Codigo = $cod;
                    $oItem2->Persona_Reporta = $datos['Identificacion_Funcionario'];
                    $oItem2->Tipo = "Compra";
                    $oItem2->Id_Acta_Recepcion_Compra = $id_Acta_Recepcion;
                    $oItem2->save();
                    $id_no_conforme = $oItem2->getId();
                    unset($oItem2);

                    /*AQUI GENERA QR */
                    $qr = generarqr('noconforme', $id_no_conforme, '/IMAGENES/QR/');
                    $oItem = new complex("No_Conforme", "Id_No_Conforme", $id_no_conforme);
                    $oItem->Codigo_Qr = $qr;
                    $oItem->save();
                    unset($oItem);
                    /*HASTA AQUI GENERA QR */
                }

                $oItem2 = new complex('Producto_No_Conforme', 'Id_Producto_No_Conforme');
                $oItem2->Id_Producto = $item["Id_Producto"];
                $oItem2->Id_No_Conforme = $id_no_conforme;
                $oItem2->Id_Compra = $datos["Id_Orden_Compra"];
                $oItem2->Tipo_Compra = $datos["Tipo"];
                $oItem2->Id_Acta_Recepcion = $id_Acta_Recepcion;
                $oItem2->Cantidad = $item["Cantidad_No_Conforme"];
                $oItem2->Id_Causal_No_Conforme = $item['No_Conforme'];
                $oItem = new complex("Causal_No_Conforme", "Id_Causal_No_Conforme", $item['No_Conforme']);
                $oItem2->Observaciones = $oItem->Nombre;
                unset($oItem);
                $oItem2->save();
                unset($oItem2);
            }
        }
    }
}


foreach ($productosRecibidos as $id => $cantidadRecibida) {
    $cantidadOrden = getCantidadOrdenCompra($id,  $comparar['Id_Orden_Nacional'], $tipoCompra,$cantidadRecibida['Id_Producto_Orden_Compra'] );
    if ($cantidadRecibida['Cantidad'] < $cantidadOrden) {
        if (!$genero_no_conforme) {
            $genero_no_conforme = true;
            $configuracion = new Configuracion();
            $cod = $configuracion->getConsecutivo('No_Conforme', 'No_Conforme');
            $oItem2 = new complex('No_Conforme', 'Id_No_Conforme');
            $oItem2->Codigo = $cod;
            $oItem2->Persona_Reporta = $datos['Identificacion_Funcionario'];
            $oItem2->Tipo = "Compra";
            $oItem2->Id_Acta_Recepcion_Compra = $id_Acta_Recepcion;
            $oItem2->save();
            $id_no_conforme = $oItem2->getId();
            unset($oItem2);

            /*AQUI GENERA QR */
            $qr = generarqr('noconforme', $id_no_conforme, '/IMAGENES/QR/');
            $oItem = new complex("No_Conforme", "Id_No_Conforme", $id_no_conforme);
            $oItem->Codigo_Qr = $qr;
            $oItem->save();
            unset($oItem);
            /* HASTA AQUI GENERA QR */
        }

        $oItem2 = new complex('Producto_No_Conforme', 'Id_Producto_No_Conforme');
        $oItem2->Id_Producto = $id;
        $oItem2->Id_No_Conforme = $id_no_conforme;
        $oItem2->Id_Compra = $datos["Id_Orden_Compra"];
        $oItem2->Tipo_Compra = $datos["Tipo"];
        $oItem2->Id_Acta_Recepcion = $id_Acta_Recepcion;
        $oItem2->Cantidad = number_format($cantidadOrden - $cantidadRecibida['Cantidad'], 0, "", "");
        $oItem2->Id_Causal_No_Conforme = 2;
        $oItem2->Observaciones = "PRODUCTO NO LLEGO EN FISICO";
        $oItem2->save();
        unset($oItem2);
    }
}
$i = -1;
if ($facturas[count($facturas) - 1]["Factura"] == "") {
    unset($facturas[count($facturas) - 1]);
}
foreach ($facturas as $fact) {
    $i++;
    $oItem = new complex('Factura_Acta_Recepcion', 'Id_Factura_Acta_Recepcion');
    $oItem->Id_Acta_Recepcion = $id_Acta_Recepcion;
    $oItem->Factura = strtoupper($fact["Factura"]);
    $oItem->Fecha_Factura = $fact["Fecha_Factura"];

    if (!empty($_FILES["archivos$i"]['name'])) {
        $posicion1 = strrpos($_FILES["archivos$i"]['name'], '.') + 1;
        $extension1 = substr($_FILES["archivos$i"]['name'], $posicion1);
        $extension1 = strtolower($extension1);
        $_filename1 = uniqid() . "." . $extension1;
        $_file1 = $MY_FILE . "ARCHIVOS/FACTURAS_COMPRA/" . $_filename1;

        $subido1 = move_uploaded_file($_FILES["archivos$i"]['tmp_name'], $_file1);
        if ($subido1) {
            @chmod($_file1, 0777);
            $oItem->Archivo_Factura = $_filename1;
        }
    }

    //HABILITAR ESTA LINEA PARA COLOCAR EL NOMBRE DEL ARCHIVO DESDE EL ARRAY QUE VIENE DEL NUEVO METODO PARA GUARDAR LOS ARCHIVOS
    // $oItem->Archivo_Factura = $nombres_archivos[$i];

    $oItem->Id_Orden_Compra = $datos['Id_Orden_Compra'];
    $oItem->Tipo_Compra = $datos['Tipo'];
    $oItem->save();
    $id_factura = $oItem->getId();
    unset($oItem);

    if (count($fact['Retenciones']) > 0) {

        foreach ($fact['Retenciones'] as $rt) {

            if ($rt['Valor'] > 0) {
                $oItem = new complex("Factura_Acta_Recepcion_Retencion", "Id_Factura_Acta_Recepcion_Retencion");
                $oItem->Id_Factura = $id_factura;
                $oItem->Id_Retencion = $rt['Id_Retencion'];
                $oItem->Id_Acta_Recepcion = $id_Acta_Recepcion;
                $oItem->Valor_Retencion = $rt['Valor'] != 0 ? round($rt['Valor'], 0) : '0';
                $oItem->save();
                unset($oItem);
            }
        }
    }
}

// Actualizando datos del producto
$h = -1;
foreach ($datosProductos as $value) {
    $h++;
    $oItem = new complex('Producto', 'Id_Producto', $value["Id_Producto"]);

    if ($oItem->Id_Subcategoria != $value["Id_Subcategoria"]) {
        $oItem->Id_Subcategoria = $value["Id_Subcategoria"];
    }
    if ($oItem->Peso_Presentacion_Regular != $value["Peso"]) {
        $oItem->Peso_Presentacion_Regular = $value["Peso"];
    }
    if (isset($_FILES["fotos$h"]['name'])) {
        $posicion2 = strrpos($_FILES["fotos$h"]['name'], '.') + 1;
        $extension2 = substr($_FILES["fotos$h"]['name'], $posicion2);
        $extension2 = strtolower($extension2);
        $_filename2 = uniqid() . "." . $extension2;
        $_file2 = $MY_FILE . "IMAGENES/PRODUCTOS/" . $_filename2;

        $subido2 = move_uploaded_file($_FILES["fotos$h"]['tmp_name'], $_file2);
        if ($subido2) {
            @chmod($_file2, 0777);
            $oItem->Imagen = $_filename2;
        }
    }
    $oItem->save();
    unset($oItem);
}

//cambiar el estado de la compra a RECIBIDA
switch ($tipoCompra) {

    case "Nacional": {
            $oItem = new complex('Orden_Compra_Nacional', 'Id_Orden_Compra_Nacional', $detalleCompra['id']);
            $oItem->getData();
            $oItem->Estado = "Recibida";
            $oItem->save();
            unset($oItem);
            break;
        }
    case "Internacional": {
            $oItem = new complex('Orden_Compra_Internacional', 'Id_Orden_Compra_Internacional', $detalleCompra['id']);
            $oItem->getData();
            $oItem->Estado = "Recibida";
            $oItem->save();
            unset($oItem);
            break;
        }
}
if ($contador == 0) {
    $resultado['mensaje'] = "Se ha guardado correctamente el acta de recepcion";
    $resultado['tipo'] = "success";
} else {
    $resultado['mensaje'] = "Se ha guardado correctamente el acta de recepcion con los productos No Conformes";
    $resultado['tipo'] = "success";
}

//Consultar el codigo del acta y el id de la orden de compra
$query_codido_acta = 'SELECT
                            Codigo,
                            Id_Orden_Compra_Nacional
                        FROM
                            Acta_Recepcion
                        WHERE
                            Id_Acta_Recepcion = ' . $id_Acta_Recepcion;

$oCon = new consulta();
$oCon->setQuery($query_codido_acta);
$acta_data = $oCon->getData();
unset($oCon);

//Guardando paso en el seguimiento del acta en cuestion
$oItem = new complex('Actividad_Orden_Compra', "Id_Acta_Recepcion_Compra");
$oItem->Id_Orden_Compra_Nacional = $acta_data['Id_Orden_Compra_Nacional'];
$oItem->Id_Acta_Recepcion_Compra = $id_Acta_Recepcion;
$oItem->Identificacion_Funcionario = $datos['Identificacion_Funcionario'];
$oItem->Detalles = "Se recibio el acta con codigo " . $acta_data['Codigo'];
$oItem->Fecha = date("Y-m-d H:i:s");
$oItem->Estado = 'Recepcion';
$oItem->save();
unset($oItem);

if ($no_conforme_devolucion) {
    $oItem = new complex('No_Conforme', 'Id_No_Conforme', $no_conforme_devolucion);
    $oItem->Estado = 'Cerrado';
    $oItem->save();
    unset($oItem);
}

//GUARDAR MOVMIMIENTO CONTABLE ACTA*/
$datos_movimiento_contable['Id_Registro'] = $id_Acta_Recepcion;
$datos_movimiento_contable['Numero_Comprobante'] = $datos['Codigo'];
$datos_movimiento_contable['Nit'] = $datos['Id_Proveedor'];
$datos_movimiento_contable['Productos'] = $datosProductos;
$datos_movimiento_contable['Facturas'] = $facturas;

$contabilizar->CrearMovimientoContable('Acta Recepcion', $datos_movimiento_contable);

echo json_encode($resultado);

//$oitem = new Complex("Producto_Acta_Recepcion" , "Id_Producto_Acta_Recepcion");

function GetCodigoCum($id_producto)
{

    $query = '
        SELECT
            Codigo_Cum
        FROM Producto
        WHERE
            Id_Producto = ' . $id_producto;

    $oCon = new consulta();
    $oCon->setQuery($query);
    $cum = $oCon->getData();
    unset($oCon);

    return $cum['Codigo_Cum'];
}

function ValidarCodigo($codigo)
{
    $estado = false;

    $query = 'SELECT
        Codigo
    FROM Acta_Recepcion
    WHERE
        Codigo = "' . $codigo . '"';

    $oCon = new consulta();
    $oCon->setQuery($query);
    $acta = $oCon->getData();
    unset($oCon);
    if ($acta['Codigo']) {
        $estado = true;
    }
    return $estado;
}

function GetInfoRetencionesProveedor($idProveedor)
{
    $query = "
        SELECT
            Tipo_Retencion,
            Tipo_Reteica,
            Id_Plan_Cuenta_Reteica,
            Id_Plan_Cuenta_Retefuente
        FROM Proveedor
        WHERE
            Id_Proveedor = '$idProveedor'";

    //CONDICIONES ADICIONALES
    // AND (Tipo_Retencion IS NOT NULL AND Tipo_Retencion <> 'N/A' AND Tipo_Retencion <> 'Autorretenedor')
    // AND (Tipo_Reteica IS NOT NULL AND Tipo_Reteica <> 'N/A')
    // AND (Id_Plan_Cuenta_Retefuente IS NOT NULL || Id_Plan_Cuenta_Reteica IS NOT NULL)

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('simple');
    $proveedor = $oCon->getData();
    unset($oCon);

    return $proveedor;
}

function getCantidadOrdenCompra($value, $id_orden, $tipoCompra, $id_producto_orden = 0)
{
    $query = "SELECT	Cantidad
    FROM Producto_Orden_Compra_$tipoCompra
    WHERE (Id_Producto = $value AND Id_Orden_Compra_$tipoCompra = $id_orden ) or ( Id_Producto_Orden_Compra_$tipoCompra = $id_producto_orden)";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $cantidad = $oCon->getData()['Cantidad'];
    unset($oCon);
    return (int)$cantidad;
}
