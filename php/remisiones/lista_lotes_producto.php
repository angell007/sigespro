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
          i.Id_Inventario as IdInventario, i.Id_Producto as IdProducto
          FROM Producto p 
         INNER JOIN Inventario i 
         on p.Id_Producto=i.Id_Producto
         INNER JOIN Bodega b 
          WHERE i.Id_Bodega =' .$idBodega ;


$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$productos = $oCon->getData();
unset($oCon);

$i=-1;
foreach($productos as $producto){ $i++;
    $oLista = new lista("Inventario");
    $oLista->setRestrict("Id_Producto","=",$producto["Id_Producto"]);
    $oLista->setRestrict("Id_Bodega","=",$idBodega);
    $oLista->setOrder("Fecha_Vencimiento","ASC");
    $lotes = $oLista->getList();
    unset($oLitsa);
    $productos[$i]["Lotes"]=$lotes;

}

echo json_encode($productos);

?>