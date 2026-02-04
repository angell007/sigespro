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
   
$query= GetQuery();


$queryObj->SetQuery($query);
$turnos = $queryObj->ExecuteQuery('Multiple');



echo json_encode($turnos);

function GetQuery(){
	global $id;

	$query="SELECT A.Id_Auditoria, CONCAT(P.Primer_Nombre,' ',P.Segundo_Nombre,' ',P.Primer_Apellido,' ',P.Segundo_Apellido) as NombrePaciente, P.Id_Paciente, 
	IFNULL(A.Archivo, '') as Archivo, IFNULL(A.Id_Servicio,'') as Id_Servicio,IFNULL(A.Id_Tipo_Servicio,'') as Id_Tipo_Servicio 
	FROM Auditoria A INNER JOIN Paciente P ON A.Id_Paciente=P.Id_Paciente INNER JOIN Punto_Turnero PT ON A.Punto_Pre_Auditoria = PT.Id_Punto_Dispensacion WHERE PT.Id_Turneros = $id AND A.Origen = 'Dispensador' AND A.Dispensador_Preauditoria IS NULL  ";

	return $query;
}




?>