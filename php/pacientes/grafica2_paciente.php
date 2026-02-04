<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = 'SELECT d.Nombre , count(p.Id_Departamento) as conteo FROM `Paciente` p INNER JOIN Departamento d ON d.Id_Departamento = p.Id_Departamento Group by p.`Id_Departamento`' ;
    
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$conteo = $oCon->getData();
unset($oCon);

$query1 = 'SELECT p.`EPS` FROM `Paciente` p INNER JOIN Departamento d ON d.Id_Departamento = p.Id_Departamento Group by p.`EPS`' ;
$oCon= new consulta();
$oCon->setQuery($query1);
$oCon->setTipo('Multiple');
$eps = $oCon->getData();
unset($oCon);

$resultado['eps'] = $eps;
$resultado['datos'] = $conteo;

echo json_encode($resultado);