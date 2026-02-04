<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.http_response.php');
require_once('../../class/class.configuracion.php');
require_once('../../class/class.qr.php');

if (isset($_REQUEST['Validar']) && $_REQUEST['Validar'] != "") {
    echo "aqui";exit;
    $condicion .= "AND  D.Codigo='".$_REQUEST['dis']."'";
}

$query = 'SELECT * 
            FROM dispensacion
            WHERE Estado_Acta = "Validado" ';
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$datos = $oCon->getData();
unset($oCon);

echo json_encode($datos);
