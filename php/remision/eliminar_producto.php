<?php

use phpDocumentor\Reflection\Types\Null_;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id_producto = ( isset( $_REQUEST['producto'] ) ? $_REQUEST['producto'] : '' );

$query="SELECT PR.*,(SELECT R.Tipo_Origen FROM Remision R WHERE R.Id_Remision=PR.Id_Remision) as Tipo_Origen, (SELECT R.Estado FROM Remision R WHERE R.Id_Remision=PR.Id_Remision) as Estado  FROM  Producto_Remision PR WHERE PR.Id_Producto_Remision=$id_producto";

// echo $query; exit;
$oCon= new consulta();
$oCon->setQuery($query);
$datos = $oCon->getData();
unset($oCon);

// $oItem = new complex("Producto_Remision","Id_Producto_Remision",(INT)$id_producto);
// $oItem->Id_Remision=NULL;
// $oItem->save();
// unset($oItem);

$query ="UPDATE Producto_Remision set Id_Remision = NULL where Id_Producto_Remision= $id_producto";

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->getData();
unset($oCon);

$oItem = new complex("Inventario_Nuevo","Id_Inventario_Nuevo",$datos['Id_Inventario_Nuevo']);
$inventario=$oItem->getData();
$delete = false;

if($datos['Tipo_Origen']=='Bodega' && $datos['Estado']=='Pendiente'){
  
        //code...
        $cantidadfinal=(INT)$inventario['Cantidad_Apartada']-(INT)$datos['Cantidad'];
        if($cantidadfinal<0){
            $cantidadfinal=0;
        }
        $oItem->Cantidad_Apartada=number_format($cantidadfinal,0,"","");
        $oItem->save();
        unset($oItem);
        $delete = true;
  
}else{
    
 
        $cantidadfinal=(INT)$inventario['Cantidad']+(INT)$datos['Cantidad'];
        if($cantidadfinal<0){
            $cantidadfinal=0;
        }
        $oItem->Cantidad=number_format($cantidadfinal,0,"","");
        $oItem->save();
        $delete = true;

    unset($oItem);
}


if ($delete) {
    $resultado["mensaje"]="Se ha eliminado el producto de la remision!";
    $resultado["tipo"]="success";
}else{
    $resultado["mensaje"]="ERROR! no se pudo descontar la cantidad del inventario!";
    $resultado["tipo"]="error";
}

echo json_encode($resultado);

?>