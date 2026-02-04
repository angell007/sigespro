<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$query = "SELECT Id_Medio_Magnetico AS value, CONCAT(Codigo_Formato,' - ',Nombre_Formato) AS label FROM Medio_Magnetico WHERE Estado = 'Activo' AND Tipo_Medio_Magnetico = 'Especial'";

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$lista = $oCon->getData();
unset($oCon);

echo json_encode($lista);
?>