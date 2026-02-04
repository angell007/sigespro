<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$query = 'SELECT T.*
             FROM Tipo_Servicio T
             WHERE T.Nombre NOT IN ("EVENTO")' ;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$tiposervicios = $oCon->getData();
unset($oCon);



echo json_encode($tiposervicios);

?>