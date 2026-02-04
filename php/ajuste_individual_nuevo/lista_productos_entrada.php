<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

     $query = 'SELECT
     PRD.Id_Producto,
     CONCAT_WS(
         " ",
         PRD.Nombre_Comercial,
         CONCAT("(", PRD.Principio_Activo),
         PRD.Presentacion,
         PRD.Concentracion,
         PRD.Cantidad,
         CONCAT(PRD.Unidad_Medida, ")"),
         "LAB -",
         PRD.Laboratorio_Comercial,
         "CUM:",
         PRD.Codigo_Cum
     ) AS Nombre,
     CONCAT_WS(
         " ",
         PRD.Nombre_Comercial,
         CONCAT("(", PRD.Principio_Activo),
         PRD.Presentacion,
         PRD.Concentracion,
         PRD.Cantidad,
         CONCAT(PRD.Unidad_Medida, ")"),
         "LAB -",
         PRD.Laboratorio_Comercial,
         "CUM:",
         PRD.Codigo_Cum
     ) AS Nombre_Producto,
     PRD.Laboratorio_Comercial,
     PRD.Codigo_Cum,
     PRD.Embalaje,
     "" AS Lote,
     "" AS Cantidad,
     IFNULL(CP.Costo_Promedio,0)  AS Costo,
     "" AS Fecha_Vencimiento,
     "" AS Observaciones
 FROM
     Producto PRD
     LEFT JOIN Costo_Promedio CP on CP.Id_Producto = PRD.Id_Producto
 WHERE
     PRD.Codigo_Barras IS NOT NULL AND PRD.Estado = "Activo" ';
    
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$productos = $oCon->getData();
unset($oCon);

echo json_encode($productos);

 
?>