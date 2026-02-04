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
             CONCAT( P.Principio_Activo, " ", P.Presentacion, " ", P.Concentracion, " (", P.Nombre_Comercial,") ", P.Cantidad," ", P.Unidad_Medida, " " ) as Nombre ,
              I.Costo as CostoUnitario,
              I.Lote as Lote,
              I.Cantidad as Cantidad,
              I.Id_Inventario as Id_Inventario,
              I.Codigo_CUM as Codigo_CUM,
              P.Invima as Invima,
              P.Fecha_Vencimiento_Invima as Fecha_Vencimiento,
              P.Laboratorio_Generico as Laboratorio_Generico
          FROM Producto P
          inner join Inventario I
          on P.Id_Producto=I.Id_Producto
           INNER JOIN Bodega b
          ON I.Id_Bodega = b.Id_Bodega 
          WHERE I.Cantidad>0 
          AND b.Id_Bodega =' .$idBodega ;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);

echo json_encode($resultado);

?>