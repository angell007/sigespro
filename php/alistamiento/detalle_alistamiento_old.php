<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$oLista = new lista("Remision");
$oLista->setRestrict("Estado_Alistamiento","=","2");
$remisiones =  $oLista->getList();
unset($oLista);

$i=-1;
foreach($remisiones as $remision){ $i++;

    $oItem = new complex($remision["Tipo_Origen"],"Id_".$remision["Tipo_Origen"],$remision["Id_Origen"]);
    $or=$oItem->getData();
    unset($oItem);
    $remisiones[$i]["NombreOrigen"]=$or["Nombre"];
    
    $oItem = new complex($remision["Tipo_Destino"],"Id_".$remision["Tipo_Destino"],$remision["Id_Destino"]);
    $or=$oItem->getData();
    unset($oItem);
    $remisiones[$i]["NombreDestino"]=$or["Nombre"];
}

echo json_encode($remisiones);
?>