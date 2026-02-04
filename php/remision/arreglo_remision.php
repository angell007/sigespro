<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query='SELECT PRR.*, I.Lote as Lote_Inventario, I.Id_Producto as Id_Prod, I.Id_Inventario as Id_inventario_Real,CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion," (",P.Nombre_Comercial, ") ", P.Cantidad," ", 			
         P.Unidad_Medida, ". ","LAB - ",P.Laboratorio_Generico, " ") as Nombre_Final FROM Producto_Remision PRR
LEFT JOIN Inventario I
On PRR.Lote=I.Lote
INNER JOIN Producto P
On PRR.Id_Producto=P.Id_Producto
';


$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$productos= $oCon->getData();
unset($oCon);
var_dump($productos);

foreach($productos as $producto){
  	$oItem = new complex('Inventario',"Id_Inventario",$producto['Id_inventario_Real']);
        $oItem->Cantidad_Apartada=$producto["Cantidad"]+ $oItem->Cantidad_Apartada;
        $oItem->save();
        unset($oItem);
        
        $oItem = new complex('Producto_Remision',"Id_Producto_Remision",$producto['Id_Producto_Remision']);
        $oItem->Id_Inventario=$producto["Id_inventario_Real"];
        $oItem->Id_Producto=$producto["Id_Prod"];
        $oItem->Nombre_Producto=$producto["Nombre_Final"];
        //var_dump($oItem);
        $oItem->save();
        unset($oItem);

}
echo json_encode($resultado);


?>