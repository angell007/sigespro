<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' ); 

$oLista = new lista('No_Conforme');
$oLista->setRestrict("Id_No_Conforme","=",$id);
$lista= $oLista->getlist();
unset($oLista);

$resultado['respuesta'] = $lista[0]['Estado'];

echo json_encode($resultado);    

