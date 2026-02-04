<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.http_response.php');
include_once('../../class/class.utility.php');


$http_response = new HttpResponse();
$queryObj = new QueryBaseDatos();
$util = new Utility();

$id= ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$tablero = ( isset( $_REQUEST['tablero'] ) ? $_REQUEST['tablero'] : 'No' );
  
if ($tablero == 'Dispensario') {
	$oItem=new complex("Auditoria","Id_Auditoria",$id);
	$auditoria = $oItem->getData();
	$oItem->Id_Dispensacion='0';
	$oItem->save();
	unset($oItem);
}else{
	$oItem=new complex("Auditoria","Id_Auditoria",$id);
	$auditoria = $oItem->getData();
	$oItem->Dispensador_Preauditoria='0';
	$oItem->save();
	unset($oItem);
}

$turnos = array();

echo json_encode($turnos);

// function GetQueryTurnos(){
// 	global $id;

// 	$query="SELECT A.Id_Auditoria, CONCAT(P.Primer_Nombre,' ',P.Segundo_Nombre,' ',P.Primer_Apellido,' ',P.Segundo_Apellido) as NombrePaciente, P.Id_Paciente, 
// 	IFNULL(A.Archivo, '') as Archivo, IFNULL(A.Id_Servicio,'') as Id_Servicio,IFNULL(A.Id_Tipo_Servicio,'') as Id_Tipo_Servicio
// 	FROM Auditoria A INNER JOIN Paciente P ON A.Id_Paciente=P.Id_Paciente INNER JOIN Punto_Turnero PT ON A.Punto_Pre_Auditoria = PT.Id_Punto_Dispensacion WHERE PT.Id_Turneros = $id AND A.Origen = 'Dispensador' AND A.Dispensador_Preauditoria IS NULL  ";

// 	return $query;
// }

// function GetTurnero($id){
// 	global $queryObj;
// 	$query = 'SELECT T.Id_Turneros FROM Punto_Turnero T WHERE T.Id_Punto_Dispensacion='.$id ;

// 	$queryObj->SetQuery($query);
// 	$turnero = $queryObj->ExecuteQuery('simple');	
// 	return $turnero['Id_Turneros'];
// }




?>