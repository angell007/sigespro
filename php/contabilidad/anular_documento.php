<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.contabilizar.php');
require('./funciones.php');

$datos = isset($_REQUEST['datos']) ? $_REQUEST['datos'] : false;

if ($datos) {
    $datos = json_decode($datos, true);

    $resultado = anularDocumento($datos);
    
}

echo json_encode($resultado);
          
?>