<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

date_default_timezone_set('America/Bogota');


require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once('../../class/class.configuracion.php');
include_once('../../class/class.consulta.php');

$productos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$productos = (array) json_decode($productos,true );

//consultar datos de la remision 

$query='SELECT R.* FROM Remision R WHERE R.Id_Remision='.$productos['Id_Remision'];
$oCon= new consulta();
$oCon->setQuery($query);
$remision = $oCon->getData();
unset($oCon);

//Cambiar la cantidad en inventario 

if($productos['Nuevo_Id_Inventario']){
    $query='SELECT I.* FROM Inventario_Nuevo I WHERE I.Id_Inventario_Nuevo='.$productos['Nuevo_Id_Inventario'];
    $oCon= new consulta();
    $oCon->setQuery($query);
    $inventario = $oCon->getData();
    unset($oCon);
    $cantidad_disponible=$inventario['Cantidad']-($inventario['Cantidad_Apartada']+$inventario['Cantidad_Seleccionada']);

    if($remision['Tipo_Origen']=='Bodega' && $remision['Estado']=='Pendiente'){
    
    
        if($productos['Cantidad']<=$cantidad_disponible){
            $oItem = new complex('Inventario_Nuevo',"Id_Inventario_Nuevo",$productos['Nuevo_Id_Inventario']);
            $cantidad=number_format($productos['Cantidad'],0,"","");
            $cantidad_apartada=$inventario['Cantidad_Apartada']+$cantidad;
            $oItem->Cantidad_Apartada=number_format($cantidad_apartada,0,"","");
            $oItem->save();
            unset($oItem);
            $productos['Id_Inventario_Nuevo']=$productos['Nuevo_Id_Inventario'];
        }else if($cantidad_disponible>0){
            $oItem = new complex('Inventario_Nuevo',"Id_Inventario_Nuevo",$productos['Nuevo_Id_Inventario']);
            $cantidad=number_format($productos['Cantidad'],0,"","");
            $oItem->Cantidad_Apartada=number_format($cantidad_disponible,0,"","");
            $oItem->save();
            unset($oItem);
            $productos['Cantidad']=$cantidad_disponible;
            $productos['Id_Inventario_Nuevo']=$productos['Nuevo_Id_Inventario'];
        }
    }else{
      
    if($productos['Cantidad']<=$cantidad_disponible){
        $oItem = new complex('Inventario_Nuevo',"Id_Inventario_Nuevo",$productos['Nuevo_Id_Inventario']);
        $cantidad=number_format($productos['Cantidad'],0,"","");
        $cantidad_apartada=$inventario['Cantidad']-$cantidad;
        if($cantidad_apartada<0){
            $cantidad_apartada=0;
        }
        $oItem->Cantidad=number_format($cantidad_apartada,0,"","");
        $oItem->save();
        unset($oItem);
        $productos['Id_Inventario_Nuevo']=$productos['Nuevo_Id_Inventario'];
    }else if($cantidad_disponible>0){
        $oItem = new complex('Inventario_Nuevo',"Id_Inventario_Nuevo",$productos['Nuevo_Id_Inventario']);
        $cantidad=number_format($productos['Cantidad'],0,"","");
        $oItem->Cantidad=number_format('0',0,"","");
        $oItem->save();
        unset($oItem);
        $productos['Cantidad']=$cantidad_disponible;
        $productos['Id_Inventario_Nuevo']=$productos['Nuevo_Id_Inventario'];
    }
    }
    
  
}

$oItem=new complex('Producto_Remision',"Id_Producto_Remision",$productos['Id_Producto_Remision']);
$cantidadtemporal=$oItem->Cantidad;
$id_inventario_antiguo=$oItem->Id_Inventario_Nuevo;
$cantidadfinal1=$oItem->Cantidad_Total;
$cantidad=number_format($productos['Cantidad'],0,"","");
if($cantidadtemporal>$cantidad){
    $cantidadfinal=($cantidad-$cantidadtemporal)+$cantidadfinal1;
}else if ($cantidadtemporal<$cantidad){
    $cantidadfinal=($cantidad-$cantidadtemporal)+$cantidadfinal1;
}
$oItem->Cantidad=number_format($productos['Cantidad'],0,"","");
$oItem->Cantidad_Total=number_format($cantidadfinal,0,"","");
$oItem->Lote=$productos['Lote'];
$oItem->Fecha_Vencimiento=$productos['Fecha_Vencimiento'];
$oItem->Id_Inventario_Nuevo=$productos['Id_Inventario_Nuevo'];
$oItem->save();
unset($oItem);


$query='SELECT I.* FROM Inventario_Nuevo I WHERE I.Id_Inventario_Nuevo='.$id_inventario_antiguo;
$oCon= new consulta();
$oCon->setQuery($query);
$inventario2 = $oCon->getData();
unset($oCon);
 if($remision['Tipo_Origen']=='Bodega' && $remision['Estado']=='Pendiente'){
    $cantidad_final=$inventario2['Cantidad_Apartada']-$cantidadtemporal;
    if($cantidad_final<0){
        $cantidad_final=0;
    }

    $oItem=new complex('Inventario_Nuevo',"Id_Inventario_Nuevo",$id_inventario_antiguo);
    $oItem->Cantidad_Apartada=number_format($cantidad_final,0,"","");
    $oItem->save();
    unset($oItem);
 }else{
    $cantidad_final=$inventario2['Cantidad']+$cantidadtemporal;
    if($cantidad_final<0){
        $cantidad_final=0;
    }

    $oItem=new complex('Inventario_Nuevo',"Id_Inventario_Nuevo",$id_inventario_antiguo);
    $oItem->Cantidad=number_format($cantidad_final,0,"","");
    $oItem->save();
    unset($oItem);
 }



$resultado['mensaje']="Se ha Guardado Correctamente!";
$resultado['tipo']="success";

echo json_encode($resultado);

?>