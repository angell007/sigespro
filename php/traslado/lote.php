<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');


$oLista = new lista("Inventario");
$oLista->setOrder("Fecha_Vencimiento","ASC");
$lotes= $oLista->getlist();
unset($oLista);


$i=0;
$nuevos[$i]["label"] = '';
$nuevos[$i]["value"] = '';
foreach($lotes as $lote){ 
    if ($punto["Lote"] != null && $punto["Id_Producto"] != null ){
    $i++;
    $nuevos[$i]["label"] = $punto["Lote"];
    $nuevos[$i]["value"] = $punto["Id_Producto"];
    }
}

echo json_encode($nuevos);

?>
