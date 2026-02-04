<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );
$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$productos = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );
$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$datos = (array) json_decode($datos);
$productos = (array) json_decode($productos , true);

if(isset($id)&&$id!=""){
    
    $oItem = new complex($mod,"Id_".$mod,$id);
    
}else{
     $oItem = new complex('Configuracion','Id_Configuracion',1);
    $nc = $oItem->getData();
    
    $oItem->Producto=$oItem->Producto+1;
    $oItem->save();
    $num_cotizacion=$nc["Producto"];
    unset($oItem);
    
    $cod = "COT".sprintf("%05d", $num_cotizacion); 
    
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

unset($productos[count($productos)-1]);

foreach($productos as $producto){
    $oItem = new complex('Producto_Orden_Compra',"Id_Producto_Orden_Compra");
    $producto["Id_Orden_Compra_Nacional"]=$id_venta;
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