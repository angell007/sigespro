<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$oLista = new lista("Oficina");
$oficinas= $oLista->getlist();
unset($oLista);

$i=-1;
foreach($oficinas as $oficina){ $i++;

	$oItem = new complex("Municipio","Id_Municipio",$oficina["Id_Municipio"]);
	$mun = $oItem->getData();
	unset($oItem);
	
	$oLista = new lista("Caja");
	$oLista->setRestrict("Id_Oficina","=",$oficina["Id_Oficina"]);
	$cajas= $oLista->getlist();
	unset($oLista);
	
	$oficinas[$i]["Municipio"]=$mun["Nombre"];
	$oficinas[$i]["Cajas"] = count($cajas);
	
	
}

echo json_encode($oficinas);
?>