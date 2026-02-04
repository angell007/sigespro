<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$id = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );

$oLista = new lista("Recaudo");
$oLista->setRestrict("Identificacion_Funcionario","=",$id);
$oLista->setRestrict("Fecha","LIKE",date("Y-m-d"));
$oLista->setOrder("Fecha","DESC");
$recaudos= $oLista->getlist();
unset($oLista);

$i=-1;
foreach($recaudos as $recaudo){ $i++;
	$recaudos[$i]["Fecha"]=date("d/m/Y H:i",strtotime($recaudo["Fecha"]));
	$recaudos[$i]["Valor"]="$ ".number_format($recaudo["Valor"],0,",",".");
}

echo json_encode($recaudos); 
?>