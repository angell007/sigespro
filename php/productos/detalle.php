<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$eps = ( isset( $_REQUEST['eps'] ) ? $_REQUEST['eps'] : '' );
$id_punto_dispensacion = ( isset( $_REQUEST['punto_dispensacion'] ) ? $_REQUEST['punto_dispensacion'] : '' );

$query = 'SELECT Id_Cliente
          FROM Cliente
          Where Nombre LIKE "%'.$eps.'%"' ;
          
$oCon= new consulta();
$oCon->setQuery($query);
$cliente = $oCon->getData();
unset($oCon);

$query = 'SELECT Id_Contrato
          FROM Contrato
          Where Id_Cliente = '.$cliente["Id_Cliente"] ;
          
$oCon= new consulta();
$oCon->setQuery($query);
$contrato = $oCon->getData();
unset($oCon);




$query = 'SELECT 
            CONCAT(
            Producto.Principio_Activo, " ",
            Producto.Presentacion, " ",
            Producto.Concentracion, " (",
            Producto.Nombre_Comercial,") ",
            Producto.Cantidad," ",
            Producto.Unidad_Medida, " "
            ) as Nombre,
            Producto.Id_Producto,
            Producto.Codigo_Cum as Cum,
            Inventario.Fecha_Vencimiento as Vencimiento,
            Inventario.Lote as Lote,
            Inventario.Id_Inventario as IdInventario,
            Inventario.Cantidad,
            Producto_Contrato.Precio as Precio
          FROM Producto 
          inner join Inventario 
          on Producto.Id_Producto=Inventario.Id_Producto AND Inventario.Id_Punto_Dispensacion = '.$id_punto_dispensacion.'
          INNER JOIN Producto_Contrato
          ON Producto.Codigo_Cum = Producto_Contrato.Cum AND Producto_Contrato.Id_Contrato = '.$contrato["Id_Contrato"].'
          WHERE Inventario.Cantidad>0
          Order by Inventario.Fecha_Vencimiento ASC' ;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);


echo json_encode($resultado);

?>