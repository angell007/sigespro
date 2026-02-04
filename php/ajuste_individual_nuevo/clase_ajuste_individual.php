<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = "SELECT * FROM Clase_Ajuste_Individual WHERE Tipo IN ('General', 'Entrada')";
    
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$entradas = $oCon->getData();
unset($oCon);

$query = "SELECT * FROM Clase_Ajuste_Individual WHERE Tipo IN ('General', 'Salida')";
    
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$salidas = $oCon->getData();
unset($oCon);

$resultado['entradas'] = $entradas;
$resultado['salidas'] = $salidas;

echo json_encode($resultado);


?>