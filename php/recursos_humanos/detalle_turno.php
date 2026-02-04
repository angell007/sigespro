<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

var_dump($id);
exit;
$query = ' SELECT *  FROM Hora_Turno WHERE Id_Turno='.$id; 

echo $query;
exit;


$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado= $oCon->getData();
unset($oCon);


echo json_encode($resultado);
?>