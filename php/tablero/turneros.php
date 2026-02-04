<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['Id'] ) ? $_REQUEST['Id'] : '' );


$query = 'SELECT T.Id_Turneros FROM Punto_Turnero T WHERE T.Id_Punto_Dispensacion='.$id ;


$oCon= new consulta();
$oCon->setQuery($query);
$turnero = $oCon->getData();
unset($oCon);


echo json_encode($turnero);
          
?>