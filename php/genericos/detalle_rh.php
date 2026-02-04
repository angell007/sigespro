<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );
$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

if($mod!="Funcionario"){
    $query = 'SELECT D.*
    FROM '.$mod.' D
    WHERE D.Id_'.$mod.' = '.$id ;
}else{
    $query = 'SELECT D.*
    FROM '.$mod.' D
    WHERE D.Identificacion_'.$mod.' = '.$id ;
}

 
$oCon= new consulta();
$oCon->setQuery($query);
$detalle = $oCon->getData();
unset($oCon);

echo json_encode($detalle,JSON_UNESCAPED_UNICODE);
?>