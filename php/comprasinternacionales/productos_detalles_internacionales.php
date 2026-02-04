<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' ); 

$query1  = 'SELECT 
                OCI.Fecha as Fecha , OCI.Observacion as Observacion, OCI.Codigo as Codigo, OCI.Fecha_Llegada as FechaLlegada, 
                OCI.Linea as Linea, OCI.Id_Funcionario as Funcionario, OCI.Nit as NIT, OCI.Id_Bodega as Bodega 
            FROM Orden_Compra_Internacional OCI 
            INNER JOIN Producto_Orden_Compra_Internacional POCI 
                on POCI.Id_Producto_Orden_Compra_Internacional=OCI.Id_Orden_Compra_Internacional 
            WHERE OCI.Id_Orden_Compra_Internacional ='.$id;
          

$oCon= new consulta();
$oCon->setQuery($query1);
$dis = $oCon->getData();
unset($oCon);

$query2 = '
          SELECT 
           POCI.Total as Subtotal, POCI.Iva as IVA, P.Id_Producto as Producto,  I.Id_Inventario as Inventario , I.Id_Dispositivo as Dispositivo,
           I.Lote as Lote, POCI.Cantidad as Cantidad, POCI. Iva as Iva, P.Principio_Activo as producto, POCI.Cantidad as cantidad,
           I.Costo as Costo_unitario
        
          FROM Producto_Orden_Compra_Internacional POCI
          INNER JOIN Inventario I
          on I.Id_Inventario= POCI.Id_Inventario
         INNER JOIN  Producto P
          on  P.Id_Producto= I.Id_Producto
          WHERE POCI.Id_Producto_Orden_Compra_Internacional  = '.$id ;
          
$oCon= new consulta();
$oCon->setQuery($query2);
$productos = $oCon->getData();
unset($oCon);          

$resultado["Datos"]=$dis;
$resultado["Productos"]=$productos;

echo json_encode($resultado);
?>