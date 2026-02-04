<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

echo json_encode([]);
exit;

$idBodega = ( isset( $_REQUEST['IdBodega'] ) ? $_REQUEST['IdBodega'] : '' );

if(isset($idBodega)&$idBodega!="" ){

$query = 'SELECT 
          CONCAT( p.Principio_Activo, " ", p.Presentacion, " ", p.Concentracion, " (", p.Nombre_Comercial,") ", p.Cantidad," ", p.Unidad_Medida, " " ) as Nombre ,
          i.Costo as CostoUnitario,
          i.Lote as Lote,
          i.Cantidad as CantidadDisponible,
          i.Id_Inventario as IdInventario,
          i.Codigo_CUM as cum,
          p.Invima as Invima,
          p.Fecha_Vencimiento_Invima as Fecha_Vencimiento,
          p.Laboratorio_Generico as Laboratorio_Generico
          FROM Producto p , Inventario i , Bodega b 
          WHERE i.Id_Bodega = b.Id_Bodega 
          AND i. Id_Producto = p.Id_Producto 
          AND b.Id_Bodega =' .$idBodega ;
}else{
    $query = 'SELECT 
          CONCAT( p.Principio_Activo, " ", p.Presentacion, " ", p.Concentracion, " (", p.Nombre_Comercial,") ", p.Cantidad," ", p.Unidad_Medida, " " ) as Nombre ,
          i.Costo as CostoUnitario,
          i.Lote as Lote,
          i.Cantidad as CantidadDisponible,
          i.Id_Inventario as IdInventario,
          i.Codigo_CUM as cum,
          p.Invima as Invima,
          p.Fecha_Vencimiento_Invima as Fecha_Vencimiento,
          p.Laboratorio_Generico as Laboratorio_Generico
          FROM Producto p , Inventario i 
          WHERE i. Id_Producto = p.Id_Producto';
}

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);

echo json_encode($resultado);

?>