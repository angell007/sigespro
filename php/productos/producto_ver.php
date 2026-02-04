<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query ='SELECT P.*, CONCAT( P.Principio_Activo, " ",
            P.Presentacion, " ",
            P.Concentracion, " (",
            P.Nombre_Comercial,") ",
            P.Cantidad," ",
            P.Unidad_Medida
            ) as Nombre,
            S.Nombre as Subcategoria, 
            (Select Costo_Promedio from Costo_Promedio Where Id_Producto = P.Id_Producto) as Costo_Promedio
            FROM Producto P
            LEFT JOIN Subcategoria S
            ON P.Id_Subcategoria=S.Id_Subcategoria
            WHERE P.Id_Producto = '.$id ;


$oCon= new consulta();
$oCon->setQuery($query);
$productover = $oCon->getData();
unset($oCon);

echo json_encode($productover);
?>