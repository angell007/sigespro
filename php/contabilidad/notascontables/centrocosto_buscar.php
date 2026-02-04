<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$query = 'SELECT CONCAT(Codigo, " - ", Nombre) AS Nombre, Id_Centro_Costo FROM Centro_Costo WHERE Movimiento = "Si" AND Estado = "Activo"' ;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$centrocosto = $oCon->getData();
unset($oCon);


echo json_encode($centrocosto);
          
?>