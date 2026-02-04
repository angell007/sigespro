<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$query = "SELECT C.* FROM Cliente C 
INNER JOIN Contrato CT ON C.Id_Cliente=CT.Id_Cliente 
INNER JOIN Eps E ON C.Id_Cliente = E.Nit WHERE E.Nit IS NOT NULL";

$con = new consulta();
$con->setQuery($query);
$con->setTipo('Multiple');
$resultado = $con->getData();
unset($con);

echo json_encode($resultado);

?>