<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = "SELECT * FROM Cheque_Consecutivo WHERE Estado = 'Activo'";

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();

foreach ($resultado as $i => $valor) {
    $resultado[$i]['value'] = $valor['Prefijo'] . str_pad($valor['Consecutivo'],4,'0',STR_PAD_LEFT);
    $resultado[$i]['label'] = $valor['Prefijo'] . str_pad($valor['Consecutivo'],4,'0',STR_PAD_LEFT);
}

echo json_encode($resultado);
?>