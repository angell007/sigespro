<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$tipo = ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : '' );

$oCon = new consulta();

if ($tipo == 'Bodega') {
    $oCon->setQuery("SELECT Id_Bodega_Nuevo as Id_Bodega , Nombre FROM Bodega_Nuevo ORDER BY Nombre");
    $oCon->setTipo('Multiple');
    $resultado = $oCon->getData();
    unset($oCon);
} else {
    $oCon->setQuery("SELECT * FROM Punto_Dispensacion ORDER BY Nombre");
    $oCon->setTipo('Multiple');
    $resultado = $oCon->getData();
    unset($oCon);
}

echo json_encode($resultado);


?>
