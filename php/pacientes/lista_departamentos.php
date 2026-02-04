<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = "SELECT CONCAT_WS('-',Id_Departamento,Codigo) AS Ids, Nombre FROM Departamento";

$Con = new consulta();
$Con->setQuery($query);
$Con->setTipo('Multiple');
$resultado = $Con->getData();
unset($Con);

echo json_encode($resultado);
?>