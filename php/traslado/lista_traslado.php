<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');


$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' ); 

$link = mysql_connect('localhost', 'corvusla_proh', 'Proh2018') or die('No se pudo conectar: ' . mysql_error());
mysql_select_db('corvusla_proh') or die('No se pudo seleccionar la base de datos');


$query1 = 'SELECT B.Nombre as Nombre_Bodega, PD.Nombre as Nombre_Punto, T.Observaciones as Observacion, PT.Nombre_Producto as Producto, PT.Cantidad, B.Id_Bodega, PD.Id_Punto_Dispensacion
FROM Traslado T
INNER JOIN Producto_Traslado PT
ON T.Id_Traslado=PT.Id_Traslado
INNER JOIN Bodega B
ON T.Id_Bodega=B.Id_Bodega
INNER JOIN Punto_Dispensacion PD
ON T.Id_Punto_Dispensacion=PD.Id_Punto_Dispensacion
WHERE T.Id_Traslado='.$id;
          
          
$result = mysql_query($query1) or die('Consulta fallida: ' . mysql_error());
$productos = [];

while($lista=mysql_fetch_assoc($result)){
    $productos[]=$lista;
}
@mysql_free_result($productos);
mysql_close($link);

echo json_encode($productos);

?>