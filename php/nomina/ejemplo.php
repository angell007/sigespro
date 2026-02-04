<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.nomina.php');
include_once('../../class/class.parafiscales.php');
include_once('../../class/class.provisiones.php');




$funcionario=new CalculoNomina(91487884,2,'2019-09-16','2019-09-28','Nomina');
$funcionario=$funcionario->CalculosNomina();   

var_dump($funcionario);
   

?>

