<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = 'SELECT A.Id_Auditoria, A.Id_Tipo_Servicio, P.Id_Paciente, CONCAT(P.Primer_Nombre," ",P.Segundo_Nombre," ",P.Primer_Apellido," ",P.Segundo_Apellido) as NombrePaciente, R.Nombre as NombreRegimen, 
P.EPS, N.Nombre as Nombre_Nivel, TS.Nombre as Nombre_Tipo_Servicio, A.Archivo
FROM Auditoria A
INNER JOIN Paciente P
ON A.Id_Paciente=P.Id_Paciente
INNER JOIN Tipo_Servicio TS
ON A.Id_Tipo_Servicio=TS.Id_Tipo_Servicio

INNER JOIN Regimen R
ON P.Id_Regimen=R.Id_Regimen
INNER JOIN Nivel N
ON P.Id_Nivel=N.Id_Nivel
WHERE A.Id_Turnero is null AND A.Estado<>"Auditado" AND A.Id_Auditoria='.$id;


$oCon= new consulta();
$oCon->setQuery($query);
$auditoria = $oCon->getData();
unset($oCon);

echo json_encode($auditoria);

?>