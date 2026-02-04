<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$oLista = new lista("Paciente");
$oLista->setItems(100);
$pacientes= $oLista->getList(1);
unset($oLista);


$i=-1;
foreach($pacientes as $paciente){ $i++;
    $oItem = new complex("Departamento","Id_Departamento",$paciente["Id_Departamento"]);
    $dep = $oItem->getData();
    $pacientes[$i]["Departamento"]=$dep["Nombre"];
    
}

//var_dump($pacientes);
echo json_encode($pacientes, JSON_UNESCAPED_UNICODE);
?>