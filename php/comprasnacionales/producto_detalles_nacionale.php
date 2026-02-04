<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' ); 

$query1  = 'SELECT OCN.Id_Proveedor as Proveedor, OCN.Id_Bodega as Bodega, OCN.Fecha_Entrega_Probable as FechaEntregaProbable, OCN.Observaciones as Observaciones
FROM  Orden_Compra_Nacional OCN
INNER JOIN Producto_Orden_Compra_Nacional POC
on POC.Id_Orden_Compra_Nacional=OCN.Id_Orden_Compra_Nacional
WHERE OCN.Id_Orden_Compra_Nacional ='.$id;

$oCon= new consulta();
$oCon->setQuery($query1);
$dis = $oCon->getData();
unset($oCon);         

$query2 = 'SELECT 
           POC.Total as Subtotal, P.Id_Producto as Producto,  I.Id_Inventario as Inventario , 
           I.Lote as Lote, POC.Cantidad as Cantidad, POC. Iva as Iva, P.Principio_Activo as producto, POC.Cantidad as cantidad,
           I.Costo as Costo_unitario
        
          FROM Producto_Orden_Compra POC
          INNER JOIN Inventario I
          on I.Id_Inventario= POC.Id_Inventario
         INNER JOIN  Producto P
          on  P.Id_Producto= I.Id_Producto
          WHERE POC.Id_Producto_Orden_Compra = '.$id ;

$oCon= new consulta();
$oCon->setQuery($query1);
$productos = $oCon->getData();
unset($oCon);         

$resultado["Datos"]=$dis;
$resultado["Productos"]=$productos;

echo json_encode($resultado);
?>