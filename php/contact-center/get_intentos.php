<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.consulta.php');
require_once __DIR__ . '/../../config/start.inc.php';

$response = array();

if (isset($_REQUEST['id_dispensacion'])) {
    $id = $_REQUEST['id_dispensacion'];
    
    $query = "SELECT I.* 
              FROM Intentos_Call_Center I 
              WHERE I.Id_Dispensacion = '$id' 
              ORDER BY I.Fecha DESC";
              
    $oCon = new consulta();
    $oCon->setTipo('Multiple');
    $oCon->setQuery($query);
    $response['data'] = $oCon->getData();
    unset($oCon);
} else {
    $response['data'] = [];
}

echo json_encode($response);
?>