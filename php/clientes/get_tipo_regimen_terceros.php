<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.consulta.php');

$oCon = new consulta();
$query = "SELECT Id_Regimen_Tercero, Nombre, Estado FROM Regimen_Tercero";
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$regimenes = $oCon->getData();

if ($regimenes) {
    echo json_encode($regimenes);
} else {
    echo json_encode(array('mensaje' => 'No se encontraron registros.'));
}

?>
