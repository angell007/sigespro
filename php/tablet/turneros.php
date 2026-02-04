<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');


$oLista = new lista("Turneros");
$oLista->setOrder("Nombre","ASC");
$turneros= $oLista->getlist();
unset($oLista);


$i=0;
$nuevos[$i]["label"] = '';
$nuevos[$i]["value"] = '';
foreach($turneros as $punto){ $i++;
    $nuevos[$i]["label"] = $punto["Nombre"];
    $nuevos[$i]["value"] = $punto["Id_Turneros"];
}

echo json_encode($nuevos);

?>
