<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');



$query = 'SELECT R.*, PC.Nombre as Cuenta FROM Tipo_Egreso R LEFT JOIN Plan_Cuentas PC ON R.Cuenta=PC.Id_Plan_Cuentas ';


$oCon= new consulta();

$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$egreso = $oCon->getData();
unset($oCon);



echo json_encode($egreso);
?>