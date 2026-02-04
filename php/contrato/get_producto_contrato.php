<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/PHPExcel/IOFactory.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$cum = isset($_REQUEST['cum']) ? $_REQUEST['cum'] : false;
$Id_Contrato = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;

// echo $cum;
// echo $Id_Contrato;
// exit;
getProductoByCum($cum, $Id_Contrato);

function getProductoByCum($cum,$Id_Contrato){
    $query = 'SELECT *
                FROM Producto_Contrato         
                WHERE Id_Contrato = "'.$Id_Contrato.'" AND Cum = "'.$cum.'" ';
    
    $oCon= new consulta();
    $oCon->setTipo('Simple');
    $oCon->setQuery($query);
    $response = $oCon->getData();
    unset($oCon);
    
    echo json_encode($response);
}


