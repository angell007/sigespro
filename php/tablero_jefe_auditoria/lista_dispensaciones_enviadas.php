<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
 
require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;

$query ='SELECT A.*, D.Codigo, D.Tipo, D.Numero_Documento, CONCAT(P.Primer_Nombre," ", P.Segundo_Nombre," ",P.Primer_Apellido," ",P.Segundo_Apellido) as Nombre_Paciente, D.Id_Dispensacion, "No" as Cumple
FROM Dispensacion D
INNER JOIN Correspondencia C
ON D.Id_Correspondencia = C.Id_Correspondencia
INNER JOIN Auditoria A
ON A.Id_Dispensacion = D.Id_Dispensacion
INNER JOIN Paciente P
ON D.Numero_Documento=P.Id_Paciente WHERE D.Estado_Correspondencia = "Enviada"
 AND D.Id_Correspondencia =  '.$id.' ORDER BY A.Id_Auditoria DESC';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$datos = $oCon->getData();
unset($oCon);

echo json_encode($datos);


?>