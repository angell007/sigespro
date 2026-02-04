<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$idRemision = ( isset( $_REQUEST['remision'] ) ? $_REQUEST['remision'] : '' );
$listaGanancia = ( isset( $_REQUEST['lista'] ) ? $_REQUEST['lista'] : '' );

$idRemision = (array) json_decode($idRemision);

if (count($idRemision) > 0) {

    if (count($idRemision) == 1) {

      $idRemision = implode(",",$idRemision);
      
      $query = 'SELECT 
        GROUP_CONCAT(CONCAT( pr.Id_Producto_Remision  )) AS Id_Producto_Remision, 
        pr.Id_Inventario_Nuevo as Id_Inventario , pr.Lote as Lote, SUM(pr.Cantidad) as Cantidad,  IF(CONCAT( p.Principio_Activo, " ",
        p.Presentacion, " ",
        p.Concentracion, " (", p.Nombre_Comercial,") ",
        p.Cantidad," ",
        p.Unidad_Medida, " LAB-", p.Laboratorio_Comercial )="" OR CONCAT( p.Principio_Activo, " ",
        p.Presentacion, " ",
        p.Concentracion, " (", p.Nombre_Comercial,") ",
        p.Cantidad," ",
        p.Unidad_Medida, " LAB-", p.Laboratorio_Comercial ) IS NULL, CONCAT(p.Nombre_Comercial," LAB-", p.Laboratorio_Comercial), CONCAT( p.Principio_Activo, " ",
        p.Presentacion, " ",
        p.Concentracion, " (", p.Nombre_Comercial,") ",
        p.Cantidad," ",
        p.Unidad_Medida, " LAB-", p.Laboratorio_Comercial )) as producto,
      p.Invima as Invima,
      p.Id_Producto,
      pr.Fecha_Vencimiento as Fecha_Vencimiento,
      pr.Id_Remision as Id_Remision,
      pr.Descuento,
      p.Laboratorio_Generico,
      IF (IFNULL((SELECT Precio FROM Precio_Regulado WHERE Codigo_Cum=p.Codigo_Cum ORDER BY Precio desc LIMIT 1),0) <  pr.Precio AND IFNULL((SELECT Precio FROM Precio_Regulado WHERE Codigo_Cum=p.Codigo_Cum ORDER BY Precio desc LIMIT 1),0)>0 , IFNULL((SELECT Precio FROM Precio_Regulado WHERE Codigo_Cum=p.Codigo_Cum ORDER BY Precio desc LIMIT 1),0),pr.Precio   )  as Precio_Venta,
      sum(pr.Subtotal) AS Subtotal,
      pr.Impuesto
  FROM 
  Producto_Remision pr
  INNER JOIN Producto p ON pr.Id_Producto = p.Id_Producto
  WHERE pr.Id_Remision IN  ('.$idRemision.') GROUP BY pr.Id_Producto, pr.Lote, pr.Precio'  ;
    } else {

      $idRemision = implode(",",$idRemision);
      
      $query = 'SELECT 
        GROUP_CONCAT(CONCAT( pr.Id_Producto_Remision  )) AS Id_Producto_Remision, 
        pr.Id_Inventario_Nuevo as Id_Inventario , pr.Lote as Lote,
        SUM(pr.Cantidad) as Cantidad,  
        IF(CONCAT( p.Principio_Activo, " ",
        p.Presentacion, " ",
        p.Concentracion, " (", p.Nombre_Comercial,") ",
        p.Cantidad," ",
        p.Unidad_Medida, " LAB-", p.Laboratorio_Comercial )="" OR CONCAT( p.Principio_Activo, " ",
        p.Presentacion, " ",
        p.Concentracion, " (", p.Nombre_Comercial,") ",
        p.Cantidad," ",
        p.Unidad_Medida, " LAB-", p.Laboratorio_Comercial ) IS NULL, CONCAT(p.Nombre_Comercial," LAB-", p.Laboratorio_Comercial), CONCAT( p.Principio_Activo, " ",
        p.Presentacion, " ",
        p.Concentracion, " (", p.Nombre_Comercial,") ",
        p.Cantidad," ",
        p.Unidad_Medida, " LAB-", p.Laboratorio_Comercial )) as producto,
      p.Invima as Invima,
      p.Id_Producto,
      pr.Fecha_Vencimiento as Fecha_Vencimiento,
      pr.Id_Remision as Id_Remision,
      pr.Descuento,
      p.Laboratorio_Generico,
      IF (IFNULL((SELECT Precio FROM Precio_Regulado WHERE Codigo_Cum=p.Codigo_Cum ORDER BY Precio desc LIMIT 1),0) <  pr.Precio AND IFNULL((SELECT Precio FROM Precio_Regulado WHERE Codigo_Cum=p.Codigo_Cum ORDER BY Precio desc LIMIT 1),0)>0 , IFNULL((SELECT Precio FROM Precio_Regulado WHERE Codigo_Cum=p.Codigo_Cum ORDER BY Precio desc LIMIT 1),0),pr.Precio   )  as Precio_Venta,
      sum(pr.Subtotal) AS Subtotal,
      pr.Impuesto
  FROM 
  Producto_Remision pr
  INNER JOIN Producto p ON pr.Id_Producto = p.Id_Producto
  WHERE pr.Id_Remision IN  ('.$idRemision.') GROUP BY pr.Id_Producto, pr.Lote, pr.Precio'  ;
    }

          
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);
} else {
  $resultado = [];
}

// $idRemision = implode(",",$idRemision); 
#var_dump(implode(",",$idRemision));
#exit;



echo json_encode($resultado);

?>