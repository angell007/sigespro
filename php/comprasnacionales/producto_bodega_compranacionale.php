<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$idBodega = ( isset( $_REQUEST['IdBodega'] ) ? $_REQUEST['IdBodega'] : '' );

$query = 'SELECT 
          CONCAT( p.Principio_Activo, " ", p.Presentacion, " ", p.Concentracion, " (", p.Nombre_Comercial,") ", p.Cantidad," ", p.Unidad_Medida, " " ) as Nombre ,
          i.Costo as CostoUnitario,
          i.Lote as Lote,
          i.Cantidad as CantidadDisponible,
          i.Id_Inventario as IdInventario
          FROM Producto p , Inventario i , Bodega b 
          WHERE i.Id_Bodega = b.Id_Bodega 
          AND i. Id_Producto = p.Id_Producto 
          AND b.Id_Bodega =' .$idBodega ;

$oCon= new consulta();
$oCon->setQuery($query);
$productos = $oCon->getData();
unset($oCon);

echo json_encode($resultado);

?>