<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$bod = isset($_REQUEST['bod']) ? $_REQUEST['bod'] : false;
$pto = isset($_REQUEST['pto']) ? $_REQUEST['pto'] : false;

$query = '';

if ($bod) {
    $query = "SELECT Id_Bodega_Nuevo AS value, Nombre AS label FROM Bodega_Nuevo";
} else {
    $query = "SELECT Id_Punto_Dispensacion AS value, Nombre AS label FROM Punto_Dispensacion";
}

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultados = $oCon->getData();
unset($oCon);


echo json_encode($resultados);

?>