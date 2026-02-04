<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;
$fecha_inicial = isset($_REQUEST['fecha_inicio']) ? $_REQUEST['fecha_inicio'] : false;
$fecha_final = isset($_REQUEST['fecha_fin']) ? $_REQUEST['fecha_fin'] : false;

$condicion = '';

if ($fecha_inicial && $fecha_final) {
    $condicion .= " AND Fecha BETWEEN '$fecha_inicial' AND '$fecha_final'";
}

$query = 'SELECT DATE_FORMAT(Fecha,"%d/%m/%Y") AS Fecha,
                Entrada_Turno, Entrada_Real,    
                SEC_TO_TIME(Tiempo) AS Tiempo_Retraso
                FROM Llegada_Tarde
                WHERE Identificacion_Funcionario='.$id . $condicion ;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);

// var_dump($resultado);
echo json_encode($resultado);
?>