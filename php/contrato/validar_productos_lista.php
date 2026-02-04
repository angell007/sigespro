<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$cum = isset($_REQUEST['cum']) ? $_REQUEST['cum'] : false;
$Id_Contrato = isset($_REQUEST['Id_Contrato']) ? $_REQUEST['Id_Contrato'] : false;

// echo $cum;
// echo $Id_Contrato;
// exit;
validarCumExiste($cum, $Id_Contrato);

function validarCumExiste($cum,$Id_Contrato){
    $query = 'SELECT Id_Producto_Contrato
                FROM Producto_Contrato         
                WHERE Id_Contrato = "'.$Id_Contrato.'" AND Cum = "'.$cum.'" ';
    // echo $query;
    $oCon= new consulta();
    $oCon->setTipo('Simple');
    $oCon->setQuery($query);
    $response = $oCon->getData();
    unset($oCon);
    
    echo json_encode($response);
}
