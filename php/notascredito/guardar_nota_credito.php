<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once('../../class/class.configuracion.php');
include_once('../../class/class.consulta.php');
require_once('../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */
require_once('../notas_credito_nuevo/helper_consecutivo.php');

include_once('../../class/class.nota_credito_electronica_estructura.php');
//$configuracion = new Configuracion();

$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );
$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$productos = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );

$reso = (isset($_REQUEST['res']) ? $_REQUEST['res'] : '24');

$datos = (array) json_decode($datos);
$productos = (array) json_decode($productos , true);


$cod = generarConsecutivo();
$datos['Codigo']=$cod;

$query = "SELECT E.Id_Bodega_Nuevo 
    FROM Estiba E 
    inner Join Inventario_Nuevo I on I.Id_Estiba = E.Id_Estiba 
    inner Join Producto_Remision PR on PR.Id_Inventario_Nuevo = I.Id_Inventario_Nuevo 
    Inner Join Producto_Factura_Venta PFV on PFV.Id_Producto_Factura_Venta = PR.Id_Producto_Factura_Venta 
    where PFV.Id_Factura_Venta = $datos[Id_Factura] limit 1"; 
$oCon = new consulta();
$oCon->setQuery($query);
$bodega = $oCon->getData();



$oItem = new complex($mod,"Id_".$mod);
foreach($datos as $index=>$value) {
    $oItem->$index=$value;
}
$oItem->Id_Bodega_Nuevo = $bodega['Id_Bodega_Nuevo']?$bodega['Id_Bodega_Nuevo'] : 1; 
$oItem->save();
$id_venta = $oItem->getId();
$resultado = array();
unset($oItem);

/* AQUI GENERA QR */
$qr = generarqr('notascredito',$id_venta,'/IMAGENES/QR/');
$oItem = new complex("Nota_Credito","Id_Nota_Credito",$id_venta);
$oItem->Codigo_Qr=$qr;
$oItem->save();
unset($oItem);
/* HASTA AQUI GENERA QR */

foreach($productos as $producto){
    if($producto['Nota']){
        $oItem = new complex('Producto_Nota_Credito',"Id_Producto_Nota_Credito");
        $producto["Id_Nota_Credito"]=$id_venta;
        foreach($producto as $index=>$value) {
            $oItem->$index=$value;
        }
        $oItem->Id_Inventario = 0;
        $oItem->Id_Inventario_Nuevo = 0;
        $subtotal = $producto['Cantidad_Ingresada'] * $producto['Precio_Venta']*(1-($producto['Descuento']/100 ));
        $oItem->Cantidad=$producto['Cantidad_Ingresada'];
        $oItem->Subtotal = number_format($subtotal,2,".","");
        $oItem->Id_Motivo=$producto['Id_Motivo'];
        $oItem->save();
        unset($oItem);
    }
  
}
if($id_venta != ""){
    $resultado['mensaje'] = "Se ha guardado correctamente la nota credito con codigo: ". $datos['Codigo'];
    $resultado['tipo'] = "success";
    $resultado['Id_Nota'] = $id_venta;
    
    $fe = new NotaCreditoElectronica('Nota_Credito', $id_venta, $reso);
    $resultado['Nota'] = $fe->GenerarNota();
    unset($oCon);
}else{
    $resultado['mensaje'] = "ha ocurrido un error guardando la informacion, por favor verifique";
    $resultado['tipo'] = "error";
}

echo json_encode($resultado);

?>		