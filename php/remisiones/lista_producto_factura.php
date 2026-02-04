<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = 'SELECT 
            CONCAT( P.Principio_Activo, " ", P.Presentacion, " ", P.Concentracion, " (", P.Nombre_Comercial,") ", P.Cantidad," ", P.Unidad_Medida, " " ) as producto, 
            P.Id_Producto, 
            P.Codigo_Cum as Cum, 
            I.Fecha_Vencimiento as Vencimiento, 
            I.Lote as Lote, 
            I.Id_Inventario as Id_Inventario,
            I.Costo as Costo_unitario,
            PFV.Cantidad as Cantidad,
            PFV.Precio_Venta as Precio_Venta,
            PFV.Subtotal as Subtotal,
            PFV.Id_Factura_Venta as idFac
           FROM Producto P , Inventario I , Producto_Factura_Venta PFV 
           WHERE I.Id_Inventario = PFV.Id_Inventario AND I.Id_Producto = P.Id_Producto AND PFV.Id_Factura_Venta = '.$id  ;


$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado = $oCon->getData();
unset($oCon);

$resultado["Productos"]=$resultado;
echo json_encode($resultado);
?>