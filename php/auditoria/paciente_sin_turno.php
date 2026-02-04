<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['IdPaciente'] ) ? $_REQUEST['IdPaciente'] : '' );

$query = 'SELECT A.Id_Tipo_Servicio, TS.Tipo_Soporte, P.Id_Paciente, CONCAT(P.Primer_Nombre," ",P.Segundo_Nombre," ",P.Primer_Apellido," ",P.Segundo_Apellido) as NombrePaciente, R.Nombre
FROM Auditoria A
INNER JOIN Paciente P
ON A.Id_Paciente=P.Id_Paciente
INNER JOIN Tipo_Soporte TS
ON A.Id_Tipo_Servicio=TS.Id_Tipo_Servicio
INNER JOIN Regimen R
ON P.Id_Regimen=R.Id_Regimen
WHERE A.Id_Turnero is null AND A.Estado<>"Auditado" AND A.Id_Auditoria='.$id ;


$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);

foreach($resultados as $resultado){
    $resultado["NombrePaciente"]=$resultado[0]["NombrePaciente"];
}


echo json_encode($resultado);
          
?>