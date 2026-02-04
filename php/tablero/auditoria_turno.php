<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$punto = ( isset( $_REQUEST['Punto'] ) ? $_REQUEST['Punto'] : '' );

$query = 'SELECT A.*,P.*, CONCAT(P.Primer_Nombre," ", P.Segundo_Nombre," ",P.Primer_Apellido, " " ,P.Segundo_Apellido) as NombrePaciente
  FROM Auditoria A
   INNER JOIN Paciente P
   ON A.Id_Paciente = P.Id_Paciente
   INNER JOIN Tipo_Servicio TS
   ON A.Id_Tipo_Servicio = TS.Id_Tipo_Servicio
  WHERE A.Estado = "Auditado" AND A.Estado_Turno= "Espera" AND A.Punto_Pre_Auditoria = '.$punto;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$auditados = $oCon->getData();
unset($oCon);


$final["Turnosauditorias"]=$auditados;
$final["Cantidadauditorias"]=count($auditados);

echo json_encode($final);
?>