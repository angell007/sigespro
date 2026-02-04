<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query='SELECT A.Fecha_Preauditoria, CONCAT(FP.Nombres," ", FP.Apellidos) as FuncionarioPreauditoria, FP.Imagen, SP.Tipo_Soporte, SP.Archivo, A.Id_Auditoria
FROM Auditoria A
INNER JOIN Funcionario FP
ON A.Funcionario_Preauditoria=FP.Identificacion_Funcionario
INNER JOIN Soporte_Auditoria SP
ON A.Id_Auditoria=SP.Id_Auditoria
WHERE A.Id_Dispensacion='.$id.'
GROUP BY SP.Tipo_Soporte';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo($oCon);

$preaditoria = $oCon->getData();
unset($oCon);


$datos["Datos"]=$preaditoria;
$datos["Nombre"]=$preaditoria[0]["FuncionarioPreauditoria"];
$datos["Fecha_Preauditoria"]=$preaditoria[0]["Fecha_Preauditoria"];
$datos["Imagen"]=$preaditoria[0]["Imagen"];
 
@mysql_free_result($datos);


echo json_encode($datos);
?>