<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');


include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../helper/response.php');
// include_once('../../helper/makeConditions.php');
$modulo= (isset($_REQUEST['modulo'])?$_REQUEST['modulo']: '*');


$query = "SELECT * FROM Causal_Positiva Where Modulos like '%$modulo%'  and Estado ='Activo' ";

$oCon = new consulta();
$oCon->setQuery($query); 
$oCon->setTipo('Multiple'); 

show($oCon->getData());

?>