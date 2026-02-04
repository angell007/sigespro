<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );


$query = 'SELECT R.* FROM Remision R WHERE (R.Fase_1='.$id.' AND R.Fin_Fase1 IS NULL ) OR (R.Fase_2 ='.$id.' AND R.Fin_Fase2 IS NULL )' ;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$funcionarios = $oCon->getData();
unset($oCon);


echo json_encode($funcionarios);