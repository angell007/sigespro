<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$func = ( isset( $_REQUEST['func'] ) ? $_REQUEST['func'] : '' );

$query = "SELECT A.Id_Auditoria, CONCAT(P.Primer_Nombre,' ',P.Segundo_Nombre,' ',P.Primer_Apellido,' ',P.Segundo_Apellido) as NombrePaciente, P.Id_Paciente FROM Auditoria A INNER JOIN Paciente P ON A.Id_Paciente=P.Id_Paciente INNER JOIN Punto_Turnero PT ON A.Punto_Pre_Auditoria = PT.Id_Punto_Dispensacion WHERE A.Dispensador_Preauditoria = $func AND A.Origen = 'Dispensador' AND A.Id_Dispensacion IS NULL";

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultados = $oCon->getData();
unset($oCon);

echo json_encode($resultados);

?>