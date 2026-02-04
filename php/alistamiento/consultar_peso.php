<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query='SELECT ROUND(((PR.Cantidad/P.Cantidad_Presentacion)*P.Peso_Presentacion_Regular),2) as Peso_Total
FROM Producto_Remision PR 
INNER JOIN Producto P
ON PR.Id_Producto=P.Id_Producto
WHERE PR.Id_Producto_Remision='.$id;

$oCon= new consulta();
$oCon->setQuery($query);
$peso = $oCon->getData();
unset($oCon);

echo json_encode($peso);

?>