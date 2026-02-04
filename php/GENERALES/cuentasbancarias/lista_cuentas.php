<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$oLista = new lista("Cuenta_Bancaria");
$cuentas= $oLista->getlist();
unset($oLista);

$i=-1;
foreach($cuentas as $cuenta){ $i++;
	$oItem = new complex("Banco","Id_Banco",$cuenta["Id_Banco"]);
	$ban = $oItem->getData();
	unset($oItem);
	
	$cuentas[$i]["Saldo_Inicial"]="$ ".number_format($cuenta["Saldo_Inicial"],0,",",".");
	$cuentas[$i]["Banco"]=$ban["Nombre"];
}

echo json_encode($cuentas);
?>