<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$query = 'SELECT 
IF(CONCAT(
Producto.Principio_Activo, " ",
Producto.Presentacion, " ",
Producto.Concentracion, " (",
Producto.Nombre_Comercial,") ",
Producto.Cantidad," ",
Producto.Unidad_Medida, " "
)="" OR CONCAT(
Producto.Principio_Activo, " ",
Producto.Presentacion, " ",
Producto.Concentracion, " (",
Producto.Nombre_Comercial,") ",
Producto.Cantidad," ",
Producto.Unidad_Medida, " "
) IS NULL, CONCAT(Producto.Nombre_Comercial," LAB-", Producto.Laboratorio_Comercial), CONCAT(
Producto.Principio_Activo, " ",
Producto.Presentacion, " ",
Producto.Concentracion, " (",
Producto.Nombre_Comercial,") ",
Producto.Cantidad," ",
Producto.Unidad_Medida, " "
)) as Nombre,
Producto.Id_Producto,
Producto.Codigo_Cum as Cum,
Inventario.Fecha_Vencimiento as Vencimiento,
Inventario.Lote as Lote,
Inventario.Id_Inventario as IdInventario,
Inventario.Cantidad
FROM Inventario 
inner join Producto 
on Producto.Id_Producto=Inventario.Id_Producto
WHERE (Inventario.Cantidad-Inventario.Cantidad_Apartada)>0
Order by Inventario.Fecha_Vencimiento ASC';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);

echo json_encode($resultado);

?>