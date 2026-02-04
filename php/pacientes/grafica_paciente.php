<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = 'SELECT r.Nombre as nombre ,count(*) as conteo , (Select count(*) from Paciente) as total FROM `Paciente` p INNER JOIN Regimen r ON r.Id_Regimen = p.Id_Regimen GROUP BY p.Id_Regimen' ;
    
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$pacientes = $oCon->getData();
unset($oCon);

$i=-1;
foreach($pacientes as $paciente){$i++;
    $pacientes[$i]['porcentaje']= round(($pacientes[$i]['conteo']/$pacientes[$i]['total'])*100,2);
}

echo json_encode($pacientes);