<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

date_default_timezone_set('America/Bogota');

include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');
require_once('../../../class/class.qr.php');

include_once('./helper.orden_generales.php');
require_once('../../../class/class.awsS3.php');
require_once('../../../class/class.configuracion.php');


// $bodega = GetBodega();

$condicion = '';
$data = isset($_REQUEST['data']) && $_REQUEST['data'] != '' ? $_REQUEST['data'] : false;

$archivo = isset($_FILES['archivo']) && $_FILES['archivo'] != '' ? $_FILES['archivo'] : false;
$data = json_decode($data, true);

$productos = $data['productos'];

$cabecera = $data['cabecera'];
$cot='';

$cabecera['Archivo_Compra_Cliente'] = '';
try {
    if ($archivo) {
        $s3 = new AwsS3();
        $ruta = "ARCHIVOS/ORDEN_COMPRA/SOPORTES";
        $nombre_archivo = $s3->putObject( $ruta, $archivo);
          

        $cabecera['Archivo_Compra_Cliente'] =   $nombre_archivo;
    }

    $idOrden = guardarCabecera($cabecera);

    $cabecera['Id_Orden_Pedido'] = $idOrden;
    guardarProductos($idOrden, $productos);
    if(isset($cabecera['Id_Cotizacion']) && $cabecera['Id_Cotizacion'] != ''){
        cambiarEstadoCotizacion($cabecera['Id_Cotizacion'], $cabecera['Identificacion_Funcionario'],$idOrden);
    }
    RegistrarActividadCambio($idOrden, $cot);
    echo json_encode(['text' => "guardado con exito", 'type' => "success", 'title' => "Operacion Exitosa"]);

} catch (\Throwable $th) {
    http_response_code(500);
    echo json_encode(['text' => $th->getMessage(), 'type' => "error", 'title' => "Ha ocurrido un error"]);
}



function guardarCabecera($cabecera)
{
    $oItem = new complex('Orden_Pedido', 'Id_Orden_Pedido');
    $oItem->Id_Cliente = $cabecera['cliente']['Id_Cliente'];
    $oItem->Fecha_Probable_Entrega = $cabecera['fecha_probable_entrega'];
    $oItem->Identificacion_Funcionario = $cabecera['Identificacion_Funcionario'];
    $oItem->Observaciones = $cabecera['observaciones'];
    $oItem->Archivo_Compra_Cliente = $cabecera['Archivo_Compra_Cliente'];
    $oItem->Orden_Compra_Cliente = $cabecera['ordenCompra'];
    $oItem->Id_Cotizacion_Venta = $cabecera['Id_Cotizacion'];
    $oItem->Estado = 'Activa';
    $oItem->save();
    return $oItem->getId();
}

function guardarProductos($idOrden, $productos)
{

    foreach ($productos as $producto) {
        $q="SELECT Costo_Promedio  FROM Costo_Promedio WHERE Id_Producto = $producto[Id_Producto]";
        $oCon = new consulta();
        $oCon->setQuery($q);
        $costo = $oCon->getData()['Costo_Promedio'];

        # code...
        $query = 'INSERT INTO  Producto_Orden_Pedido 
        ( Id_Orden_Pedido, Id_Producto, Cantidad , Precio_Orden , Impuesto, Precio, Costo,Descuento, Cantidad_Compra ) VALUES(
            ' . $idOrden . ',
             ' . $producto['Id_Producto'] . ' , 
             ' . $producto['Cantidad'] . ' ,  
             ' . number_format($producto['Precio_Orden'], 2, ".", "") . ' ,
             ' . $producto['Impuesto'] . ' ,
             ' . number_format($producto['Precio_Lista'], 2, ".", "") . '  ,
             ' . number_format($producto['Costo']?$producto['Costo']: $costo, 2, ".", "") . ' ,
             ' . $producto['Descuento'] . ' , 
             0 
        )';

        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->createData();
        unset($oCon);
    }
}

function armarCompra(&$productos)
{

    $compra = [];
    foreach ($productos as $key => $prod) {


        if ($prod['Cantidad'] != $prod['Cantidad_Remision']) {
            $productos[$key]['Cantidad_Compra']   = $prod['Cantidad'] - $prod['Cantidad_Remision'];

            if (array_key_exists($prod['Proveedor'], $compra)) {
                array_push($compra[$prod['Proveedor']]['Productos'], $productos[$key]);
            } else {
                $compra[$prod['Proveedor']] = [];
                $compra[$prod['Proveedor']]['Productos'][] = $productos[$key];
            }
        } else {
            $productos[$key]['Cantidad_Compra']  = 0;
        }
    }
    return $compra;
}

function cambiarEstadoCotizacion($id_cotizacion, $funcionario, $orden){
    global $cot;
    $oItem = new complex('Cotizacion_Venta', 'Id_Cotizacion_Venta', $id_cotizacion);
    $cot = $oItem->Codigo;
    $oItem->Estado_Cotizacion_Venta = 'Aprobada';
    $oItem->save();

    unset($oItem);
    $oItem = new complex('Actividad_Cotizacion', 'Id_Actividad_Cotizacion');
    $oItem->Id_Cotizacion = $id_cotizacion;
    $oItem->Fecha = date('Y-m-d H:i:s');
    $oItem->Estado = "Aprobada";
    $oItem->Identificacion_Funcionario = $funcionario;
    $oItem->Detalles = "Se aprueba la cotizacion y se crea la Orden de pedido: OP$orden";

    $oItem->save();

}

function RegistrarActividadCambio($id, $cot)
{
    global $cabecera;

    $ActividadDis['Fecha'] = date("Y-m-d H:i:s");
    $ActividadDis["Id_Orden_Pedido"] = $id;
    $ActividadDis["Identificacion_Funcionario"] = $cabecera['Identificacion_Funcionario'];
    $ActividadDis["Detalle"] = "Se crea la OP$id de la cotizacion $cot";
    $ActividadDis["Estado"] = "Creacion";

    $oItem = new complex("Actividad_Orden_Pedido", "Id_Actividad_Orden_Pedido");
    foreach ($ActividadDis as $index => $value) {
        $oItem->$index = $value;
    }
    $oItem->save();
    unset($oItem);
}