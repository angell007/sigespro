<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

// $punto = isset($_REQUEST['punto']) ? $_REQUEST['punto'] : false;
$punto = 64;


$query = 'SELECT A.Id_Auditoria, CONCAT(P.Primer_Nombre," ",P.Segundo_Nombre," ",P.Primer_Apellido," ",P.Segundo_Apellido) as NombrePaciente, P.Id_Paciente
FROM Auditoria A
INNER JOIN Paciente P
ON A.Id_Paciente=P.Id_Paciente
WHERE A.Id_Turnero is null AND A.Estado<>"Auditado" AND A.Punto_Pre_Auditoria = '.$punto.' LIMIT 10' ;


$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);

echo json_encode($resultado);
          
?>