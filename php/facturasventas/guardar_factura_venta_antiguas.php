<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once('../../class/class.configuracion.php');
require_once('../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */

$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );
$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$productos = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );
$remision = ( isset( $_REQUEST['id_remision'] ) ? $_REQUEST['id_remision'] : '' );

$datos = (array) json_decode($datos, true);

$productos = (array) json_decode($productos , true);
$remision = (array) json_decode($remision , true);


$configuracion = new Configuracion();
#$oItem = new complex('Configuracion','Id_Configuracion',1);
#$nc = $oItem->getData();
    
#$oItem->Consecutivo=$oItem->Consecutivo+1;
#$oItem->save();
#$num_cotizacion=$nc["Consecutivo"];
#unset($oItem);

$cod = $configuracion->Consecutivo('Factura_Venta');
    
$datos['Codigo']=$cod;


    
$oItem = new complex($mod,"Id_".$mod);

foreach($datos as $index=>$value) {
    $oItem->$index=$value;
}

$oItem->save();
$id_factura = $oItem->getId();
$resultado = array();
unset($oItem);



/* AQUI GENERA QR */
$qr = generarqr('facturaventa',$id_factura,'/IMAGENES/QR/');
$oItem = new complex("Factura_Venta","Id_Factura_Venta",$id_factura);
$oItem->Codigo_Qr=$qr;
$oItem->save();
unset($oItem);
/* HASTA AQUI GENERA QR */



// unset($productos[count($productos)-1]);

foreach($productos as $producto){
    $oItem = new complex('Producto_'.$mod,"Id_Producto_".$mod);
    $producto["Id_".$mod]=$id_factura;
    foreach($producto as $index=>$value) {
        $oItem->$index=$value;
    }
    $oItem->save();
    unset($oItem);
}

if(isset($remision)&&$remision!=""){
    
    foreach($remision as $remisiones){
        $oItem = new complex('Remision_Antigua',"Id_Remision",$remisiones['id']);
        $remision["Id_Factura"]=$id_factura;
        foreach($remision as $index=>$value) {
            $oItem->$index=$value;
        }
        $oItem->save();
        unset($oItem);
        
        //echo $remisiones['id'];
    }
    
}

if($id_factura != ""){
    $resultado['mensaje'] = "Se ha guardado correctamente la cotizacion con codigo: ". $datos['Codigo'];
    $resultado['tipo'] = "success";
}else{
    $resultado['mensaje'] = "ha ocurrido un error guardando la informacion, por favor verifique";
    $resultado['tipo'] = "error";
}

echo json_encode($resultado);

?>		