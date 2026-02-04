<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$id = (isset($_REQUEST['id']) ? $_REQUEST['id'] : false);
$estado = (isset($_REQUEST['estado']) ? $_REQUEST['estado'] : false);

if ($estado=='Activo'){
    $estado = 'Inactivo';
}else{
    $estado = 'Activo';
}

if($id){
    $query = "UPDATE Encabezado_Formulario SET Estado = '".$estado."' WHERE Id_Encabezado_Formulario = ".$id;
    	$oCon= new consulta();
    	$oCon->setQuery($query);
    	$resultado = $oCon->createData();
    	unset($oCon);   
}