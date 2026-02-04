<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

//$func = ( isset( $_REQUEST['func'] ) ? $_REQUEST['func'] : '' );
$condicion = '';

if (isset($_REQUEST['pto']) && $_REQUEST['pto'] != "") {
	$condicion .= "AND A.Punto_Pre_Auditoria=$_REQUEST[pto]";
	
}
if (isset($_REQUEST['tipo']) && $_REQUEST['tipo'] != "") {	
	
	$condicion .= " AND D.Id_Tipo_Servicio='$_REQUEST[tipo]'";		
	
}
if (isset($_REQUEST['regimen']) && $_REQUEST['regimen'] != "") {	
		$condicion .= " AND P.Id_Regimen='$_REQUEST[regimen]'";
	
}


$query ='SELECT A.Id_Auditoria, A.Fecha_Preauditoria, D.Codigo, (SELECT CONCAT(S.Nombre," - ",T.Nombre) FROM Tipo_Servicio T
 INNER JOIN Servicio S ON T.Id_Servicio=S.Id_Servicio WHERE T.Id_Tipo_Servicio=D.Id_Tipo_Servicio ) as Tipo, D.Numero_Documento, 
 CONCAT(P.Primer_Nombre," ", P.Segundo_Nombre," ",P.Primer_Apellido," ",P.Segundo_Apellido) as Nombre_Paciente, 
 D.Id_Dispensacion, D.Estado_Correspondencia, (SELECT TS.Nombre FROM Tipo_Servicio TS WHERE TS.Id_Tipo_Servicio=D.Tipo_Servicio ) as Servicio,
 (SELECT R.Nombre FROM Regimen R WHERE R.Id_Regimen=P.Id_Regimen ) as Regimen
FROM Auditoria A
INNER JOIN Dispensacion D
ON A.Id_Dispensacion = D.Id_Dispensacion
INNER JOIN Paciente P
ON D.Numero_Documento=P.Id_Paciente
WHERE D.Estado_Correspondencia="Pendiente" AND A.Estado="Aceptar" AND D.Pendientes=0  AND 
D.Estado_Auditoria = "Auditada"
AND D.Estado_Dispensacion!="Anulada" '.$condicion.'
 ORDER BY D.Fecha_Actual DESC';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$datos = $oCon->getData();
unset($oCon);

echo json_encode($datos);


?>