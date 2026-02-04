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
$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );
$productos = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );

$datos = (array) json_decode($datos);
$productos = (array) json_decode($productos , true);
$datos["Identificacion_Funcionario"]=$funcionario;

if(isset($datos['Id_Orden_Compra_Internacional'])&&$datos['Id_Orden_Compra_Internacional'] != ""){
    $oItem = new complex('Producto_Orden_Compra_Internacional',"Id_Orden_Compra_Internacional",$datos['Id_Orden_Compra_Internacional']);
    $oItem->delete();
    unset($oItem);
    
    $oItem = new complex($mod,"Id_".$mod,$datos['Id_Orden_Compra_Internacional']);
    
}else{
    /*
     $oItem = new complex('Configuracion','Id_Configuracion',1);
    $nc = $oItem->getData();
    
    $oItem->Orden_Compra=$oItem->Orden_Compra+1;
    $oItem->save();
    $num_compra=$nc["Orden_Compra"];
    unset($oItem);
    
    $cod = "OCI".sprintf("%05d", $num_compra); */
    
    $cod = $configuracion->Consecutivo('Orden_Compra');
    $datos['Codigo']=$cod;
    
    $oItem = new complex($mod,"Id_".$mod);
}

foreach($datos as $index=>$value) {
    $oItem->$index=$value;
}
$oItem->save();
$id_venta = $oItem->getId();
$resultado = array();
unset($oItem);

/* AQUI GENERA QR */
$qr = generarqr('ordencomprainternacional',$id_venta,'/IMAGENES/QR/');
$oItem = new complex("Orden_Compra_Internacional","Id_Orden_Compra_Internacional",$id_venta);
$oItem->Codigo_Qr=$qr;
$oItem->save();
unset($oItem);
/* HASTA AQUI GENERA QR */

unset($productos[count($productos)-1]);

foreach($productos as $producto){
    $oItem = new complex('Producto_Orden_Compra_Internacional',"Id_Producto_Orden_Compra_Internacional");
    $producto["Id_Orden_Compra_Internacional"]=$id_venta;
    foreach($producto as $index=>$value) {
        $oItem->$index=$value;
    }
    $oItem->save();
    unset($oItem);
}

if($id_venta != ""){
    $resultado['mensaje'] = "Se ha guardado correctamente la orden de compra: ". $datos['Codigo'];
    $resultado['tipo'] = "success";
}else{
    $resultado['mensaje'] = "ha ocurrido un error guardando la informacion, por favor verifique";
    $resultado['tipo'] = "error";
}

echo json_encode($resultado);

?>		