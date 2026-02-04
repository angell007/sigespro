<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$tipo = isset($_REQUEST['tipo']) ? $_REQUEST['tipo'] : false;

$query = '';

if ($tipo == "Bodega") {
    $query = "SELECT Id_Bodega_Nuevo AS Id, Nombre FROM Bodega_Nuevo ORDER BY Nombre";
} else {
    $query = "SELECT Id_Punto_Dispensacion AS Id, Nombre FROM Punto_Dispensacion ORDER BY Nombre";
}

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultados = $oCon->getData();
unset($oCon);


echo json_encode($resultados);

?>