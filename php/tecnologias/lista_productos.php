<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = 'SELECT P.Nombre_Comercial, CONCAT_WS(" ",P.Codigo_Cum,P.Nombre_Comercial) AS Nombre, P.Id_Producto FROM Producto P GROUP BY P.Id_Producto';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$tipo_tec_mipres = $oCon->getData();
unset($oCon);

echo json_encode($tipo_tec_mipres);
?>