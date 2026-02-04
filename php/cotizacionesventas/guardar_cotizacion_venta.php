<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once('../../class/class.configuracion.php');
require_once('../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */

$configuracion = new Configuracion();

$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );
$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$productos = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );
$productosCotizados = ( isset( $_REQUEST['productos_Cot'] ) ? $_REQUEST['productos_Cot'] : '' );
$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : false );
$funcionario = ( isset( $_REQUEST['Id_Funcionario'] ) ? $_REQUEST['Id_Funcionario'] : null );
$codigoCotizacion =  ( isset( $_REQUEST['codigo'] ) ? $_REQUEST['codigo'] : '' );

$datos = (array) json_decode($datos, true);
if(!$funcionario){
    $funcionario = $datos['Id_Funcionario'];
}


$productos = (array) json_decode($productos , true);
$productosCotizados = (array) json_decode($productosCotizados , true);

if($id){
    
    $datos['Codigo']=$codigoCotizacion;
    $datos['Fecha_Documento_Edicion']=date('Y-m-d H:i:s');
    $oItem = new complex($mod,"Id_".$mod,$id);
    
}else{
    $cod= $configuracion->getConsecutivo('Cotizacion_Venta','Cotizacion');
    $datos['Codigo']= $cod;
    $oItem = new complex($mod,"Id_".$mod);
}

foreach($datos as $index=>$value) {
    $oItem->$index=$value;
}

// var_dump($oItem);
// exit;
$oItem->save();
$id_venta = $oItem->getId();
$resultado = array();
unset($oItem);

/* AQUI GENERA QR */
$qr = generarqr('cotizacionventa',$id_venta,'/IMAGENES/QR/');
$oItem = new complex("Cotizacion_Venta","Id_Cotizacion_Venta",$id_venta);
$oItem->Codigo_Qr=$qr;
$oItem->save();
unset($oItem);
/* HASTA AQUI GENERA QR */


unset($productos[count($productos)-1]);

foreach($productos as $producto){
    $oItem = new complex('Producto_Cotizacion_Venta',"Id_Producto_Cotizacion_Venta");
    $producto["Id_Cotizacion_Venta"]=$id_venta;
    $oItem->Id_Cotizacion_Venta = $producto["Id_Cotizacion_Venta"];
    $oItem->Id_Producto = $producto['Id_Producto'];
    $oItem->Cantidad = $producto['Cantidad'];
    $oItem->Observacion = $producto['Observacion'];
    $oItem->Descuento = number_format($producto['Descuento']);
    $oItem->Impuesto = number_format($producto['Impuesto']);
    $oItem->Precio_Venta = number_format($producto['Precio_Venta'],2,".","");
    $oItem->Iva = number_format($producto['Iva'],2,".","");
    $oItem->Subtotal = number_format($producto['Subtotal'],2,".","");
    $oItem->save();
    unset($oItem);
}
foreach($productosCotizados as $producto){
    $oItem = new complex('Producto_Cotizacion_Venta',"Id_Producto_Cotizacion_Venta", $producto['idPcv']);
    foreach($producto as $index=>$value) {
        $oItem->$index=$value;
    }
    $oItem->Impuesto = number_format(str_replace('%', '', $producto['Impuesto']));
    $oItem->Subtotal = number_format($producto['Subtotal'],2,".","");
    $oItem->Descuento = number_format($producto['Descuento']);
    $oItem->Iva = number_format($producto['Iva'],2,".","");
    $oItem->Precio_Venta = number_format($producto['Precio_Venta'],2,".","");
    $oItem->save();
    unset($oItem);
    
}


$act = new complex('Actividad_Cotizacion', 'Id_Actividad_Cotizacion');
$act->Id_Cotizacion = $id_venta;
$act->Fecha = date('Y-m-d H:i:s');
$act->Estado = "Edicion";
$act->Identificacion_Funcionario = $funcionario;

if($id){
    $act->Detalles = "Se editó la cotización ";
}
else{
    $act->Detalles = "Se Creó la cotización ";
}

$act->save();
unset($act);

if($id_venta != ""){
    $resultado['mensaje'] = "Se ha guardado correctamente la cotizacion con codigo: ". $datos['Codigo'];
    $resultado['tipo'] = "success";
}else{
    $resultado['mensaje'] = "ha ocurrido un error guardando la informacion, por favor verifique";
    $resultado['tipo'] = "error";
}


echo json_encode($resultado);
?>		