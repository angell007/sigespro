<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$oLista = new lista("Caja");
$cajas= $oLista->getlist();
unset($oLista);

$i=-1;
foreach($cajas as $caja){ $i++;

	$oItem = new complex("Oficina","Id_Oficina",$caja["Id_Oficina"]);
	$ofi = $oItem->getData();
	unset($oItem);

	$oItem = new complex("Municipio","Id_Municipio",$ofi["Id_Municipio"]);
	$mun = $oItem->getData();
	unset($oItem);
	
	$cajas[$i]["Municipio"]=$mun["Nombre"];
	$cajas[$i]["Oficina"] = $ofi["Nombre"];
	
	
}

echo json_encode($cajas);
?>