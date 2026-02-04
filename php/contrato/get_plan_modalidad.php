<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = 'SELECT Nombre AS label, Id_Cobertura_Plan_Beneficios as value
            FROM Cobertura_Plan_Beneficios';
$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$respuesta["Plan"] = $oCon->getData();
unset($oCon);

$query = 'SELECT Nombre as label, Id_Modalidad_Contratacion_Pago as value
            FROM Modalidad_Contratacion_Pago';         
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$respuesta['Modalidad'] = $oCon->getData();
unset($oCon);

echo json_encode($respuesta);