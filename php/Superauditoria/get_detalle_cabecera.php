<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query0 = 'SELECT A.Fecha_Preauditoria as Fecha , CONCAT("AUD00",A.ID_Auditoria) as Codigo, D.Telefono as Telefono_Punto, D.Direccion as Direccion_Punto, (SELECT D1.Codigo_Qr FROM Dispensacion D1 WHERE D1.Id_Dispensacion=A.Id_Dispensacion) as Codigo_Qr, D.Nombre as Punto_Dispensacion, (SELECT Nombre FROM Departamento WHERE Id_Departamento=D.Departamento ) as Departamento, A.Id_Dispensacion
FROM Auditoria A
INNER JOIN Punto_Dispensacion D 
ON A.Punto_Pre_Auditoria=D.Id_Punto_Dispensacion
WHERE Id_Auditoria = '.$id;

$oCon= new consulta();
$oCon->setQuery($query0);
//$oCon->setTipo('Multiple');
$auditoria = $oCon->getData();
unset($oCon);

$query4 = 'SELECT * FROM Soporte_Auditoria
WHERE Id_Auditoria =  '.$id.' ORDER BY Id_Soporte_Auditoria ASC' ;
$oCon= new consulta();
$oCon->setQuery($query4);
$oCon->setTipo('Multiple');
$soportes = $oCon->getData();
unset($oCon);

$query='SELECT P.Id_Paciente,   CONCAT_WS(" ",P.Primer_Nombre,P.Segundo_Nombre,P.Primer_Apellido,  P.Segundo_Apellido) as Nombre_Paciente,CONCAT(F.Nombres, " ",  F.Apellidos) as Funcionario, A.Archivo, D.Codigo as Codigo_Dis, (SELECT A2.Observacion FROM Actividad_Auditoria A2 WHERE A2.Id_Auditoria=A.Id_Auditoria AND A2.Estado="Validacion" AND A2.Observacion!="Sin Observacion" ORDER BY A2.Id_Actividad_Auditoria DESC  LIMIT 1) as Observacion, A.Id_Auditoria
FROM Auditoria A INNER JOIN  Dispensacion D ON A.Id_Dispensacion=D.Id_Dispensacion INNER JOIN Paciente P on D.Numero_Documento = P.Id_Paciente
INNER JOIN Funcionario F
ON A.Funcionario_Preauditoria=F.Identificacion_Funcionario
WHERE A.Id_Auditoria='.$id;

$oCon= new consulta();
$oCon->setQuery($query);
//$oCon->setTipo('Multiple');
$detalle_auditoria = $oCon->getData();
unset($oCon);



$resultado['Detalles']=$auditoria;
$resultado['Soportes']=$soportes;
$resultado['Auditoria']=$detalle_auditoria;

echo json_encode($resultado);

?>