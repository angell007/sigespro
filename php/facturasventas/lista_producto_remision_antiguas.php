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
  $idRemision = implode(",",$idRemision);

  $query = 'SELECT 
                pr.Id_Inventario as Id_Inventario , pr.Lote as Lote, SUM(pr.Cantidad) as Cantidad,  IF(CONCAT( p.Principio_Activo, " ",
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
                I.Fecha_Vencimiento as Fecha_Vencimiento,
                pr.Id_Remision as Id_Remision,
                p.Laboratorio_Generico,
                pr.Precio AS Precio_Venta,
                SUM(pr.Subtotal) AS Subtotal,
                pr.Impuesto
          FROM 
            Producto_Remision_Antigua pr
            INNER JOIN Producto p ON pr.Id_Producto = p.Id_Producto
            INNER JOIN Inventario I ON I.Id_Producto = p.Id_Producto
          WHERE pr.Id_Remision IN ('.$idRemision.') GROUP BY pr.Id_Inventario'  ;
          
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