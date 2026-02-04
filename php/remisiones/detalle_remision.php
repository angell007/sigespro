<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = "SELECT DISTINCT 
                R.Fecha_Documento as FechaDocumento, R.Fecha_Documento as Tiempo , R.Transporte_Entrega as TransporteEntrega, R.Entrega_Estimada  as EntregaEstimada, R.Guia_Entrega as GuiaEntrega, R.Observacion_Factura_Venta as Observaciones , R.Codigo , 
                C.Nombre as NombreCliente , C.Direccion as DireccionCliente, C.Ciudad as CiudadCliente ,  C.Telefono as TelefonoCliente  
            FROM Factura_Venta F , Remision_Factura_Venta R , Cliente C 
            WHERE F.Id_Cliente = C.Id_Cliente 
            AND R.Id_Remision_Factura_Venta = F.Id_Remision_Factura_Venta 
            AND R.Id_Remision_Factura_Venta =".$id ;
            
$oCon= new consulta();
$oCon->setQuery($query);
$dis = $oCon->getData();
unset($oCon);

$query2 = 'SELECT 
                CONCAT( P.Principio_Activo, " ", P.Presentacion, " ", P.Concentracion, " (", P.Nombre_Comercial,") ", P.Cantidad," ", P.Unidad_Medida, " " ) as producto, 
                P.Codigo_Cum as Cum, 
                I.Lote as Lote, 
                I.Costo as Costo_unitario, 
                PFV.Cantidad as Cantidad, 
                PFV.Precio_Venta as PrecioVenta, 
                PFV.Subtotal as Subtotal
            FROM Factura_Venta FV , Producto_Factura_Venta PFV, Inventario I, Producto P 
            WHERE PFV.Id_Factura_Venta = FV.Id_Factura_Venta 
                 AND PFV.Id_Inventario = I.Id_Inventario 
                 AND I.Id_Producto = P.Id_Producto 
                 AND FV.`Id_Remision_Factura_Venta` =  '.$id ;

$oCon= new consulta();
$oCon->setQuery($query2);
$oCon->setTipo('Multiple');
$productos = $oCon->getData();
unset($oCon);

$query3 = 'SELECT 
                Codigo , Fecha_Documento as Fecha , Observacion_Factura_Venta as Observacion
            FROM Factura_Venta  
            WHERE Id_Remision_Factura_Venta =  '.$id ;


$oCon= new consulta();
$oCon->setQuery($query3);
$oCon->setTipo('Multiple');
$factura = $oCon->getData();
unset($oCon);

$resultado["Datos"]=$dis;
$resultado["Productos"]=$productos;
$resultado["Facturas"]=$factura;

echo json_encode($resultado);