<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
$idremision = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = 'SELECT PR.Lote, IFNULL(CONCAT( P.Principio_Activo, " ",
P.Presentacion, " ",
P.Concentracion, " (", P.Nombre_Comercial,") ",
P.Cantidad," ",
P.Unidad_Medida, " LAB-", P.Laboratorio_Comercial ), CONCAT(P.Nombre_Comercial," LAB-", P.Laboratorio_Comercial)) AS Nombre_Producto, PR.Cantidad, PR.Precio, PR.Descuento, PR.Impuesto, PR.Subtotal, I.Fecha_Vencimiento
FROM Producto_Remision_Antigua PR
INNER JOIN Producto P ON PR.Id_Producto=P.Id_Producto
INNER JOIN Inventario I ON PR.Id_Producto=I.Id_Producto
WHERE PR.Id_Remision='.$idremision.' 
group BY PR.Id_Producto_Remision_Antigua
ORDER BY Nombre_Producto';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$productos = $oCon->getData();
unset($oCon);

echo json_encode($productos);
?>