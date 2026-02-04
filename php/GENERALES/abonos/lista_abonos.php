<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$id = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );


$oLista = new lista("Abono");
$oLista->setRestrict("Identificacion_Funcionario","=",$id);
$oLista->setRestrict("Fecha","LIKE",date("Y-m-d"));
$oLista->setOrder("Fecha","DESC"); 
$abonos= $oLista->getlist();
unset($oLista);

$i=-1;
foreach($abonos as $abono){ $i++;
	$abonos[$i]["Fecha"]=date("d/m/Y H:i",strtotime($abono["Fecha"]));
	$abonos[$i]["Valor"]="$ ".number_format($abono["Valor"],0,",",".");
}

echo json_encode($abonos); 
?>