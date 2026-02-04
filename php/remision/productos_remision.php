<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
$idremision = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = "SELECT PR.Lote, PR.Fecha_Vencimiento, P.Id_Categoria,
IFNULL(CONCAT( P.Principio_Activo, ' ',P.Presentacion, ' ',P.Concentracion, ' (', P.Nombre_Comercial,') ',P.Cantidad,' ',P.Unidad_Medida, ' LAB-', P.Laboratorio_Comercial ), CONCAT(P.Nombre_Comercial,' LAB-', P.Laboratorio_Comercial)) AS Nombre_Producto, 
PR.Cantidad, 
PR.Precio, PR.Descuento, PR.Impuesto, PR.Subtotal,
I.Nombre AS Grupo
FROM Producto_Remision PR
INNER JOIN Producto P ON PR.Id_Producto=P.Id_Producto
LEFT JOIN (SELECT G.Nombre, I.Id_Inventario_Nuevo
FROM Inventario_Nuevo I  
INNER JOIN Estiba E ON E.Id_Estiba=I.Id_Estiba
INNER JOIN Grupo_Estiba G ON G.Id_Grupo_Estiba = E.Id_Grupo_Estiba
) I on I.Id_Inventario_Nuevo = PR.Id_Inventario_Nuevo
WHERE PR.Id_Remision='$idremision' ORDER BY Nombre_Producto";

// echo $query; exit;
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$productos = $oCon->getData();
unset($oCon);

echo json_encode($productos);
?>