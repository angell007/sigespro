<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$query = 'SELECT * FROM Encabezado_Formulario';
            
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$datos['Formularios']= $oCon->getData();
unset($oCon);


$query = 'SELECT * FROM Cargo';
            
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$datos['Cargos']= $oCon->getData();
unset($oCon);



echo json_encode($datos);
