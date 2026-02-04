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
            OCI.Fecha as Fecha , OCI.Observacion as Observacion, OCI.Codigo as Codigo, OCI.Fecha_Llegada as FechaLlegada,
            OCI.Dia_Entrega as DiaEntrega, OCI.Linea as Linea, OCI.Id_Funcionario as Funcionario, OCI.Nit as NIT, OCI.Id_Bodega as Bodega
          FROM Orden_Compra_Internacional OCI, Producto_Orden_Compra_Internacional POCI
          WHERE OCI.Id_Orden_Compra_Internacional = POCI.Id_Orden_Compra_Internacional
          AND OCI.Id_Orden_Compra_Internacional ='.$id ;
            
$result = mysql_query($query) or die('Consulta fallida: ' . mysql_error());
$dis = mysql_fetch_assoc($result);
@mysql_free_result($dis);

$query2 = 'SELECT 
            OCI.Fecha as Fecha , OCI.Observacion as Observacion, OCI.Codigo as Codigo, OCI.Fecha_Llegada as FechaLlegada,
            OCI.Dia_Entrega as DiaEntrega, OCI.Linea as Linea, OCI.Id_Funcionario as Funcionario,
            OCI.Nit as NIT, OCI.Id_Bodega as Bodega, POCI.Id_Producto as Producto, POCI.Cantidad as Cantidad, POCI.Id_Orden_Compra
          FROM Orden_Compra_Internacional OCI, Id_Producto_Orden_Compra_Internacional POCI
          WHERE OCI.Id_Orden_Compra_Internacional ='.$id ;

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