<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$link = mysql_connect('localhost', 'corvusla_proh', 'Proh2018') or die('No se pudo conectar: ' . mysql_error());
mysql_select_db('corvusla_proh') or die('No se pudo seleccionar la base de datos');


$query = 'SELECT B.Nombre as Nombre_Bodega, PD.Nombre as Nombre_Punto, T.Observaciones, T.Fecha, T.Estado
FROM Traslado T 
INNER JOIN Bodega B
On T.Id_Bodega=B.Id_Bodega
INNER JOIN Punto_Dispensacion PD
on T.Id_Punto_Dispensacion= PD.Id_Punto_Dispensacion';

$result = mysql_query($query) or die('Consulta fallida: ' . mysql_error());
$dis = mysql_fetch_assoc($result);
mysql_close($link);

echo json_encode($dis);

?>