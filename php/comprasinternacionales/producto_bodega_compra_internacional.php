<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = 'SELECT 
          p.Nombre_Listado as Nombre ,
          i.Costo as Costo,
          i.Lote as Lote,
          i.Cantidad as Cantidad,
          i.Id_Inventario as IdInventario,
          i.Id_Producto as Id_Producto
          FROM Producto p , Inventario i
          WHERE i.Id_Producto = p.Id_Producto
          AND p.Tipo = "Material"
          AND p.Nombre_Listado is not null' ;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);

echo json_encode($resultado);

?>