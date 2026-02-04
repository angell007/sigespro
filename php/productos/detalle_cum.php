<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['cum'] ) ? $_REQUEST['cum'] : '' );
$cum1 = explode('-', $id);
            $cum1[0]? $cum1[0] = (int)$cum1[0] > 0 ? (int)$cum1[0] : $cum1[0] : '';
            $cum1[1] ? $cum1[1] = (int)$cum1[1] > 0 ? (int)$cum1[1] : $cum1[1] : '';
$cum1 = implode('-', $cum1);

$query ="SELECT P.*
            FROM Producto P
            WHERE P.Codigo_Cum in ('$id', '$cum1')" ;


$oCon= new consulta();
$oCon->setQuery($query);
$producto = $oCon->getData();
if($producto){
    echo json_encode(true);
}else{
      echo json_encode(false);
}
unset($oCon);


?>