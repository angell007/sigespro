<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$link = mysql_connect('localhost', 'corvusla_proh', 'Proh2018') or die('No se pudo conectar: ' . mysql_error());
mysql_select_db('corvusla_proh') or die('No se pudo seleccionar la base de datos');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = 'SELECT 
            OCN.Id_Bodega as Bodega, OCN.Id_Proveedor as Proveedor, OCN.Observaciones as Observaciones, 
          FROM Orden_Compra_Nacional OCN, Producto_Orden_Compra_Nacional POC
          WHERE OCN.Id_Orden_Compra_Nacional = POC.Id_Orden_Compra_Nacional
          AND OCN.Id_Orden_Compra_Nacional ='.$id ;
            
$result = mysql_query($query) or die('Consulta fallida: ' . mysql_error());
$dis = mysql_fetch_assoc($result);
@mysql_free_result($dis);

$query2 = 'SELECT 
            OCN.Fecha as Fecha , OCN.Observacion as Observacion, OCN.Codigo as Codigo, OCN.Fecha_Llegada as FechaLlegada,
            OCN.Dia_Entrega as DiaEntrega, OCN.Linea as Linea, OCN.Id_Funcionario as Funcionario,
            OCN.Nit as NIT, OCN.Id_Bodega as Bodega, POC.Id_Producto as Producto, POC.Cantidad as Cantidad, POC.Id_Orden_Compra
          FROM Orden_Compra_Nacional OCN, Producto_Orden_Compra POC
          WHERE OCN.Id_Orden_Compra_Nacional ='.$id ;

$result2 = mysql_query($query2) or die('Consulta fallida: ' . mysql_error());

$productos = [];

while($lista=mysql_fetch_assoc($result2)){
    $productos[]=$lista;
}
@mysql_free_result($productos);


$resultado["Datos"]=$dis;
$resultado["Productos"]=$productos;

echo json_encode($resultado);
 
          
?>