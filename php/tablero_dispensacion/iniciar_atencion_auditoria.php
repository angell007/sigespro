<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.querybasedatos.php');
$queryObj = new QueryBaseDatos();

$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );

$modelo = (array) json_decode(utf8_decode($modelo));

$query='SELECT TA.*, IFNULL(A.Id_Paciente, T.Identificacion_Persona) as Id_Paciente,IFNULL(A.Archivo, "") as Archivo,IFNULL(A.Id_Servicio,"") as Id_Servicio,IFNULL(A.Id_Tipo_Servicio,"") as Id_Tipo_Servicio,"Virtual" as Tipo_Turnero 
FROM Turno_Activo TA 
LEFT JOIN Auditoria A ON TA.Id_Auditoria=A.Id_Auditoria
INNER JOIN Turnero T ON TA.Id_Turnero=T.Id_Turnero
WHERE TA.Id_Turno_Activo= '.$modelo['Id_Turno_Activo'];

$queryObj->SetQuery($query);
$turno = $queryObj->ExecuteQuery('simple');


$oItem = new complex("Turnero","Id_Turnero",$modelo['Id_Turnero']);
$oItem->Estado="Atencion";
$oItem->Hora_Inicio_Atencion=date("H:i:s"); 
$oItem->Caja=$modelo['Caja']!='' ? $modelo['Caja'] : '1' ; 
$oItem->save();
$aten = $oItem->getData();
unset($oItem);




echo json_encode($turno);
?>