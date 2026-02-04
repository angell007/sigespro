<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.consulta.php');

$oCon = new consulta();
$query = "SELECT Id_Retencion_Proveedor, Nombre, Estado FROM Retencion_Proveedor";
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$retenciones = $oCon->getData();

if ($retenciones ) {
    echo json_encode($retenciones );
} else {
    echo json_encode(array('mensaje' => 'No se encontraron registros.'));
}

?>
