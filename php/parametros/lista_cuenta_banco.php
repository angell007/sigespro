<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');



$query = 'SELECT C.*, PC.Nombre as Cuenta FROM Cuenta_Banco C INNER JOIN Plan_Cuentas PC ON C.Id_Plan_Cuenta=PC.Id_Plan_Cuentas  ';


$oCon= new consulta();

$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$banco = $oCon->getData();
unset($oCon);

echo json_encode($banco);
?>