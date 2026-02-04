<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );

$oItem = new complex("Abono","Id_Abono");
foreach($datos as $index=>$value) {
    $oItem->$index=$value;
}
$oItem->save();
unset($oItem);


$oItem = new complex("Funcionario","Identificacion_Funcionario",$datos["Identificacion_Funcionario"]);
$func = $oItem->getData();
unset($oItem);

$cupo = $func["Cupo"];

$oLista = new lista("Recaudo");
$oLista->setRestrict("Identificacion_Funcionario","=",$datos["Identificacion_Funcionario"]);
$oLista->setRestrict("Fecha","LIKE",date("Y-m-d"));
$oLista->setOrder("Fecha","DESC");
$recaudos= $oLista->getlist();
unset($oLista);

$i=-1;
foreach($recaudos as $recaudo){ $i++;
	$cupo-=$recaudo["Valor"];
	$recaudos[$i]["Fecha"]=date("d/m/Y H:i",strtotime($recaudo["Fecha"]));
	$recaudos[$i]["Valor"]="$ ".number_format($recaudo["Valor"],0,",",".");
}


$oLista = new lista("Abono");
$oLista->setRestrict("Identificacion_Funcionario","=",$datos["Identificacion_Funcionario"]);
$oLista->setRestrict("Fecha","LIKE",date("Y-m-d"));
$oLista->setOrder("Fecha","DESC");
$abonos= $oLista->getlist();
unset($oLista);

$i=-1;
foreach($abonos as $abono){ $i++;
	$cupo+=$abono["Valor"];
	$abonos[$i]["Fecha"]=date("d/m/Y H:i",strtotime($abono["Fecha"]));
	$abonos[$i]["Valor"]="$ ".number_format($abono["Valor"],0,",",".");
}

$cupo_usado=$func["Cupo"]-$cupo;

$porc_usado=number_format(($cupo_usado*100)/$func["Cupo"],0,",",".");
$porc_libre=100-$porc_usado;

$final["Cupo"]=number_format($func["Cupo"],0,",",".");
$final["Libre"]=$porc_libre;
$final["Usado"]=$porc_usado;
$final["V_Usado"]="$ ".number_format($cupo_usado,0,",",".");
$final["V_Libre"]="$ ".number_format($cupo,0,",",".");
$final["Recaudos"]=$recaudos;
$final["Abonos"]=$abonos;

echo json_encode($final); 
?>