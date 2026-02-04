<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once('../../class/class.configuracion.php');

$configuracion = new Configuracion();
$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );
$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$productos = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );

$datos = (array) json_decode($datos);
$productos = (array) json_decode($productos , true);

/*$oItem = new complex('Configuracion','Id_Configuracion',1);
    $nc = $oItem->getData();
    
    $oItem->Traslado=$oItem->Traslado+1;
    $oItem->save();
    $num_traslado=$nc["Traslado"];
    unset($oItem);
    
    $cod = "NC".sprintf("%05d", $num_traslado); */
    
    $datos['Codigo']=$configuracion->Consecutivo('Traslado');
    
    $oItem = new complex($mod,"Id_".$mod);
    
    foreach($datos as $index=>$value) {
    $oItem->$index=$value;
}
$oItem->save();
$id_traslado=$oItem->getId();
echo $id_traslado;
$resultado = array();
unset($oItem);
$i=-1;
foreach($productos as $producto){
   $i++;
   $oItem = new complex('Producto_Traslado',"Id_Producto_Traslado");
    $producto["Id_Traslado"]=$id_traslado;
    $producto["Cantidad"]=$producto['Total'];
    
    foreach($producto as $index=>$value) {
        $oItem->$index=$value;
    }
    $oItem->save();
    $id_producto_traslado=$oItem->getId();
    unset($oItem);

$cantidad = $producto['Total'];
 $producto["Cantidad"]=$cantidad;

if($producto['LotesSeleccionados']!=null){
  
 foreach($producto['LotesSeleccionados'] as $lote){
       
        $cantidad_lote=0;
         $oItem = new complex('Inventario',"Id_Inventario",$lote["value"]);
         if ($cantidad>$oItem->Cantidad){
             $cantidad -= $oItem->Cantidad;
             $cantidad_lote = $oItem->Cantidad;
             $oItem->Cantidad = 0;
             
           }
          
         else{
             $oItem->Cantidad = $oItem->Cantidad - $cantidad;
             $cantidad_lote = $cantidad;
         }
         unset($oItem->Fecha_Carga);
         $inventario = $oItem->getData();
         var_dump($inventario);
         $oItem->save();
         unset($oItem);
              
         }
         
       $oItem = new complex('Inventario_Producto_Traslado',"Id_Inventario_Producto_Traslado");
        $lote["Id_Producto_Traslado"]=$id_producto_traslado;
        $lote["Cantidad"]=$cantidad_lote;
       
        $lote["Id_Inventario"]=$inventario["Id_Inventario"];
        $lote["Fecha_Vencimiento"]=$inventario["Fecha_Vencimiento"];
        $lote["Lote"]=$inventario["Lote"];
       
        var_dump($lote);
        foreach($lote as $index=>$value) {
            $oItem->$index=$value;
        }
        $oItem->save();
        unset($oItem);
        }

  
    }

    
 ?>  