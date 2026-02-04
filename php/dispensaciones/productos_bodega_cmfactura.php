<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = 'SELECT  
          CONCAT( p.Principio_Activo, " ", p.Presentacion, " ", p.Concentracion, " (", p.Nombre_Comercial,") ", p.Cantidad," ", p.Unidad_Medida, " " ) as Nombre ,
          i.Costo as precio,
          i.Lote as Lote,
          i.Id_Inventario as Id_Inventario,
          i.Codigo_CUM as Cum,
          p.Invima as Invima,
          p.Fecha_Vencimiento_Invima as Fecha_Vencimiento,
          p.Laboratorio_Generico as Laboratorio_Generico,
          p.Laboratorio_Comercial as Laboratorio_Comercial,
          p.Presentacion as Presentacion,
          PD.Cantidad_Formulada as Cantidad,
          p.Gravado as Gravado,
          "0" as Descuento,
          "0" as Precio,
          "0" as Impuesto,
          "0" as Subtotal,
          "0" as Precio_Venta_Factura
          FROM Producto_Dispensacion as PD 
          INNER JOIN Producto p
          on p.Id_Producto=PD.Id_Producto
          INNER JOIN Inventario i
          ON i.Id_Inventario = PD.Id_Inventario';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);

echo json_encode($resultado);

?>