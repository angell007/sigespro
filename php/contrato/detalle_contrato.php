<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );
$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query='SELECT CT.*, 
               C.Nombre as Nombre_Cliente , 
               CPB.Nombre as Plan, 
               MCP.Nombre as Modalidad
FROM Contrato CT
LEFT JOIN Cobertura_Plan_Beneficios CPB ON CT.Id_Cobertura_Plan_Beneficios = CPB.Id_Cobertura_Plan_Beneficios
LEFT JOIN Modalidad_Contratacion_Pago MCP ON CT.Id_Modalidad_Contratacion_Pago = MCP.Id_Modalidad_Contratacion_Pago
INNER JOIN Cliente C ON CT.Id_Cliente=C.Id_Cliente
WHERE CT.Id_Contrato='.$id;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$detalle = $oCon->getData();
unset($oCon);



echo json_encode($detalle);
?>