<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );
$punto = ( isset( $_REQUEST['punto'] ) ? $_REQUEST['punto'] : '' );
$fechainicio = ( isset( $_REQUEST['inicio'] ) ? $_REQUEST['inicio'] : '' );
$fechafin = ( isset( $_REQUEST['fin'] ) ? $_REQUEST['fin'] : '' );

$fechainicio=$fechainicio.' 00:00:00';
$fechafin=$fechafin.' 23:59:59';


$query = "SELECT IFNULL(SUM(Cuota),0) AS Cuotas FROM `Dispensacion` WHERE Estado_Dispensacion <> 'Anulada' AND DATE(Fecha_Actual) BETWEEN '$fechainicio' AND '$fechafin' AND Id_Diario_Cajas_Dispensacion IS NULL AND Id_Punto_Dispensacion=$punto AND Identificacion_Funcionario=$funcionario ";

$oCon= new consulta();
$oCon->setQuery($query);
$totalCuota = $oCon->getData();
unset($oCon);

$query = "SELECT IFNULL(SUM(Gasto),0) AS Gastos FROM `Gastos_Cajas_Dispensaciones` WHERE Fecha BETWEEN '$fechainicio' AND '$fechafin' AND Id_Punto_Dispensacion=$punto AND Identificacion_Funcionario=$funcionario";
$oCon= new consulta();
$oCon->setQuery($query);
$totalGastos= $oCon->getData();
unset($oCon);

$query = "SELECT GROUP_CONCAT(Id_Dispensacion) as Id_Dispensacion FROM `Dispensacion` WHERE Estado_Dispensacion <> 'Anulada' AND DATE(Fecha_Actual) BETWEEN '$fechainicio' AND '$fechafin' AND Id_Diario_Cajas_Dispensacion IS NULL AND Id_Punto_Dispensacion=$punto AND Identificacion_Funcionario=$funcionario";

$oCon= new consulta();
$oCon->setQuery($query);
$id_dispensaciones = $oCon->getData();
unset($oCon);

$query = "SELECT D.Codigo, CONCAT_WS(' ',P.Primer_Nombre, P.Primer_Apellido, P.Segundo_Apellido) as Paciente, D.Cuota FROM Dispensacion D
INNER JOIN Paciente P
ON D.Numero_Documento=P.Id_Paciente WHERE D.Estado_Dispensacion <> 'Anulada' AND DATE(D.Fecha_Actual) BETWEEN '$fechainicio' AND '$fechafin' AND Id_Diario_Cajas_Dispensacion IS NULL AND Id_Punto_Dispensacion=$punto AND Identificacion_Funcionario=$funcionario";

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$dispesaciones = $oCon->getData();
unset($oCon);

$resultado['Cuotas'] = $totalCuota['Cuotas'];
$resultado['Gastos'] = $totalGastos['Gastos'];
$resultado['Id_Dispensacion'] = $id_dispensaciones['Id_Dispensacion'];
$resultado['Dispensaciones'] =$dispesaciones;

echo json_encode($resultado);

?>