<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.consulta.php');

$query = 'Select * From Grupo_Estiba';
$oCon = new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$Grupo_Estibas = $oCon->getData();
unset($oCon);

if ($Grupo_Estibas) {

    $producto["Mensaje"] = 'Estibas Encontradas con éxito';
    $resultado["Tipo"] = "success";
    $resultado["Grupo_Estibas"] = $Grupo_Estibas;
} else {
    $resultado["Tipo"] = "error";
    $resultado["Titulo"] = "Error al intentar buscar las bodegas";
    $resultado["Texto"] = "Ha ocurrido un error inesperado.";
}
echo json_encode($resultado);
