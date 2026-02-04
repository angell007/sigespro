<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id  = (isset($_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$func = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );
$observacion = ( isset( $_REQUEST['observacion'] ) ? $_REQUEST['observacion'] : '' );

$observacion=utf8_decode($observacion);

$oItem = new complex('Remision','Id_Remision',$id);
$rem=$oItem->getData(); 
$oItem->Estado = "Anulada";
//$oItem->Identificacion_Funcionario=$func;
$oItem->Observacion_Anulacion=$observacion;
$oItem->save();
unset($oItem);

$query = 'SELECT PR.Id_Inventario, PR.Lote, PR.Cantidad, PR.Id_Producto
FROM Producto_Remision PR 
WHERE PR.Id_Remision='.$id;   	  
 

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$productos = $oCon->getData();
unset($oCon);

if($rem['Tipo_Origen']=='Bodega' && $rem['Estado_Alistamiento']!=2){
    foreach($productos as $producto){
       if($producto['Id_Inventario']){
                
            $oItem=new complex('Inventario_Viejo','Id_Inventario',$producto['Id_Inventario']);
            $inv=$oItem->getData(); 
            $apartada=number_format($inv["Cantidad_Apartada"],0,"","");
            $actual = number_format($producto["Cantidad"],0,"","");
            $fin = $apartada - $actual;
            if($fin<0){
                $fin=0;
            }
            $oItem->Cantidad_Apartada=number_format($fin,0,"","");
            $oItem->save();
            unset($oItem);
         }else if($producto['Id_Inventario_Nuevo']){
             $oItem=new complex('Inventario_Nuevo','Id_Inventario_Nuevo',$producto['Id_Inventario_Nuevo']);
            $inv=$oItem->getData(); 
            $apartada=number_format($inv["Cantidad_Apartada"],0,"","");
            $actual = number_format($producto["Cantidad"],0,"","");
            $fin = $apartada - $actual;
            if($fin<0){
                $fin=0;
            }
            $oItem->Cantidad_Apartada=number_format($fin,0,"","");
            $oItem->save();
            unset($oItem);
             
        }
        
    }
}elseif ($rem['Tipo_Origen']=='Punto_Dispensacion') {
    foreach($productos as $producto){
        if($producto['Id_Inventario']){
            $oItem=new complex('Inventario_Viejo','Id_Inventario',$producto['Id_Inventario']);
            $inv=$oItem->getData(); 
            $apartada=number_format($inv["Cantidad"],0,"","");
            $actual = number_format($producto["Cantidad"],0,"","");
            $fin = $apartada + $actual;
            $oItem->Cantidad=number_format($fin,0,"","");
            $oItem->save();
            unset($oItem);
        }else if($producto['Id_Inventario_Nuevo']){
             $oItem=new complex('Inventario_Nuevo','Id_Inventario_Nuevo',$producto['Id_Inventario_Nuevo']);
              $inv=$oItem->getData(); 
            $apartada=number_format($inv["Cantidad"],0,"","");
            $actual = number_format($producto["Cantidad"],0,"","");
            $fin = $apartada + $actual;
            $oItem->Cantidad=number_format($fin,0,"","");
            $oItem->save();
            unset($oItem);
            
        }    
    }
}elseif ($rem['Tipo_Origen']=='Bodega' && $rem['Estado_Alistamiento']==2) {
    foreach($productos as $producto){
        if($producto['Id_Inventario']){
            $oItem=new complex('Inventario_Viejo','Id_Inventario',$producto['Id_Inventario']);
            $inv=$oItem->getData(); 
            $apartada=number_format($inv["Cantidad"],0,"","");
            $actual = number_format($producto["Cantidad"],0,"","");
            $fin = $apartada + $actual;
            $oItem->Cantidad=number_format($fin,0,"","");
            $oItem->save();
            unset($oItem);
        }else if($producto['Id_Inventario_Nuevo']){
            $oItem=new complex('Inventario_Nuevo','Id_Inventario_Nuevo',$producto['Id_Inventario_Nuevo']);
            $inv=$oItem->getData(); 
            $apartada=number_format($inv["Cantidad"],0,"","");
            $actual = number_format($producto["Cantidad"],0,"","");
            $fin = $apartada + $actual;
            $oItem->Cantidad=number_format($fin,0,"","");
            $oItem->save();
            unset($oItem);
        }    
    }
}
 

$oItem = new complex('Actividad_Remision',"Id_Actividad_Remision");
$oItem->Id_Remision=$id;
$oItem->Identificacion_Funcionario=$func;
$oItem->Detalles="Anulo la Remision ";
$oItem->Estado="Anulada";
$oItem->save();
unset($oItem);

echo json_encode($resultado);
?>