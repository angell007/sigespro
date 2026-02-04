<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;
$estado = isset($_REQUEST['estado']) ? $_REQUEST['estado'] : false;

$query ='SELECT A.Id_Auditoria, A.Fecha_Preauditoria, D.Codigo, 
(SELECT CONCAT(S.Nombre," - ",T.Nombre) FROM Tipo_Servicio T
 INNER JOIN Servicio S ON T.Id_Servicio=S.Id_Servicio 
 WHERE T.Id_Tipo_Servicio=D.Id_Tipo_Servicio ) as Tipo, 
 D.Numero_Documento, 
 CONCAT(P.Primer_Nombre," ", P.Segundo_Nombre," ",P.Primer_Apellido," ",P.Segundo_Apellido) as Nombre_Paciente, D.Id_Dispensacion
FROM Dispensacion D
INNER JOIN Correspondencia C
ON D.Id_Correspondencia = C.Id_Correspondencia
INNER JOIN Auditoria A
ON A.Id_Dispensacion = D.Id_Dispensacion
INNER JOIN Paciente P
ON D.Numero_Documento=P.Id_Paciente WHERE D.Estado_Correspondencia = "'.$estado.'" AND D.Id_Correspondencia = '.$id.' ORDER BY A.Id_Auditoria DESC';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$datos = $oCon->getData();
unset($oCon);

echo json_encode($datos);


?>