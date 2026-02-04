<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');

$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );

$condicion = '';
 
if($mod === 'Cliente'){
    $condicion = " WHERE Estado != 'Inactivo'";
}elseif($mod === 'Resolucion'){
    $condicion = "  ORDER BY Id_Resolucion DESC";
}

$query='SELECT * FROM '.$mod.$condicion;
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);
echo json_encode($resultado);

?>