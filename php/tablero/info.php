<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$id = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );


$oItem = new complex("Funcionario","Identificacion_Funcionario",$id);
$func = $oItem->getData();
unset($oItem);

$cupo = $func["Cupo"];

$oLista = new lista("Recaudo");
$oLista->setRestrict("Identificacion_Funcionario","=",$id);
$oLista->setRestrict("Fecha","LIKE",date("Y-m-d"));
$oLista->setOrder("Fecha","DESC");
$recaudos= $oLista->getlist();
unset($oLista);

$i=-1;
foreach($recaudos as $recaudo){ $i++;
	$cupo-=$recaudo["Valor"];
}


$oLista = new lista("Abono");
$oLista->setRestrict("Identificacion_Funcionario","=",$id);
$oLista->setRestrict("Fecha","LIKE",date("Y-m-d"));
$oLista->setOrder("Fecha","DESC");
$abonos= $oLista->getlist();
unset($oLista);

$i=-1;
foreach($abonos as $abono){ $i++;
	$cupo+=$abono["Valor"];
}

$cupo_usado=$func["Cupo"]-$cupo;

$porc_usado=number_format(($cupo_usado*100)/$func["Cupo"],0,",",".");
$porc_libre=100-$porc_usado;

$final["Cupo"]=number_format($func["Cupo"],0,",",".");
$final["Libre"]=$porc_libre;
$final["Usado"]=$porc_usado;
$final["V_Usado"]="$ ".number_format($cupo_usado,0,",",".");
$final["V_Libre"]="$ ".number_format($cupo,0,",",".");

echo json_encode($final); 
?>