<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = isset($_REQUEST['Id_Lista_Ganancia']) ? $_REQUEST['Id_Lista_Ganancia'] : false;

$cond = !$id ? '' : ' WHERE Id_Lista_Ganancia = '.$id; 

$query="SELECT Id_Lista_Ganancia, CONCAT(Nombre,' - ', Porcentaje,'%') AS Nombre , Codigo FROM Lista_Ganancia $cond";

$oCon= new consulta();
$oCon->setQuery($query);

if (!$cond) {
    $oCon->setTipo('Multiple');
}

$resultado = $oCon->getData();
unset($oCon);

echo json_encode($resultado);
?>