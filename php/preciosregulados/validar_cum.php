<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

date_default_timezone_set('America/Bogota');


require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once('../../class/class.configuracion.php');
include_once('../../class/class.consulta.php');

$Cum = ( isset( $_REQUEST['Cum'] ) ? $_REQUEST['Cum'] : '' );

$query = "SELECT Id_Producto FROM Producto WHERE Codigo_Cum = '$Cum'";

$oCon = new consulta();
$oCon->setQuery($query);
$res = $oCon->getData();

if ($res) {

    $query = "SELECT Id_Precio_Regulado FROM Precio_Regulado WHERE Codigo_Cum = '$Cum'";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $res = $oCon->getData();

    if ($res) {
        $resultado['response'] = 1; // Ya el código cum está registrado en regulados.
    }
    
} else {
    $resultado['response'] = 2; // El Código Cum no es válido ó no existe.
}

echo json_encode($resultado);

?>