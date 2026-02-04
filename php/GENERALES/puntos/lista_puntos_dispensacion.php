<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$tipo=$_REQUEST['Tipo']; 
$tipo= isset($tipo)?" AND Tipo like '%$tipo%'":"";
$query = "SELECT Id_Punto_Dispensacion AS value, Nombre AS label FROM Punto_Dispensacion WHERE Estado = 'Activo' $tipo";

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$res = $oCon->getData();
unset($oCon);

echo json_encode($res);

?>