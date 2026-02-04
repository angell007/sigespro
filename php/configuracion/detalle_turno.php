<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = 'SELECT Id_Hora_Turno as Id, Dia, Hora_Inicio1,Hora_Fin1, Hora_Inicio2, Hora_Fin2 FROM Hora_Turno WHERE Id_Turno='.$id; 


$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado= $oCon->getData();
unset($oCon);


echo json_encode($resultado);
?>