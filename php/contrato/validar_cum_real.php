<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$cum = isset($_REQUEST['cum']) ? $_REQUEST['cum'] : false;

validarCumReal($cum);

function validarCumReal($cum){
    $query = 'SELECT Id_Producto
                FROM Producto
                WHERE Codigo_Cum ="'.$cum.'"';
    $oCon= new consulta();
    $oCon->setTipo('Simple');
    $oCon->setQuery($query);
    $response = $oCon->getData();
    unset($oCon);
    
    echo json_encode($response);
}



