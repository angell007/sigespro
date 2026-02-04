<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.contabilizar.php');

$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;
$resultado = [];

if ($id) {
    $query = "SELECT Id_Borrador_Contabilidad AS ID, Codigo, Datos FROM Borrador_Contabilidad WHERE Id_Borrador_Contabilidad = $id";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $resultado = $oCon->getData();
    unset($oCon);
}

echo json_encode($resultado);

          
?>