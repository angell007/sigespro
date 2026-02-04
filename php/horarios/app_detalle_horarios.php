<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$fecha_inicial = isset($_REQUEST['fecha_inicio']) ? $_REQUEST['fecha_inicio'] : "2018-05-01";
$fecha_final = isset($_REQUEST['fecha_fin']) ? $_REQUEST['fecha_fin'] : date("Y-m-d");
$funcionario = isset($_REQUEST['funcionario']) ? $_REQUEST['funcionario'] : false;


$condicion = '';
$condicion2 = '';

if ($fecha_inicial && $fecha_final) {
    $condicion .= "WHERE llt.Fecha BETWEEN '$fecha_inicial' AND '$fecha_final'";
    $condicion2 .= "WHERE Fecha BETWEEN '$fecha_inicial' AND '$fecha_final'";
    
}
$condicion.=' AND llt.Identificacion_Funcionario = '.$funcionario;
$condicion2.=' AND Identificacion_Funcionario = '.$funcionario;

$query = 'SELECT 
        COUNT(llt.Id_Llegada_Tarde) AS Llegadas_Tardes
        FROM Llegada_Tarde llt 
        '.$condicion.' GROUP BY llt.Identificacion_Funcionario' ;

$oCon= new consulta();
$oCon->setQuery($query);
//$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);

$query2 = 'SELECT * FROM Diario_Fijo 
        '.$condicion2 ;

$oCon= new consulta();
$oCon->setQuery($query2);
$oCon->setTipo('Multiple');
$resultado["Horarios"] = $oCon->getData();
unset($oCon);

echo json_encode($resultado);
?>