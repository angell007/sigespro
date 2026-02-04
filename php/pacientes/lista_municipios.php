<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id_dep = isset($_REQUEST['Dep']) ? $_REQUEST['Dep'] : false;

$query = "SELECT CONCAT_WS('-',Id_Municipio,Codigo,Codigo_Dane) AS Ids, Nombre FROM Municipio WHERE Id_Departamento=$id_dep";

$Con = new consulta();
$Con->setQuery($query);
$Con->setTipo('Multiple');
$resultado = $Con->getData();
unset($Con);

echo json_encode($resultado);
?>