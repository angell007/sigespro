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
$tipo= ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : '' );
   
$query= GetQuery();


$queryObj->SetQuery($query);
$turnos = $queryObj->ExecuteQuery('Multiple');



echo json_encode($turnos);

function GetQuery(){
	global $id, $tipo;


	if($tipo!='Auditoria'){
		$query='SELECT T.*,IFNULL((SELECT Icono FROM Prioridad_Turnero WHERE Id_Prioridad_Turnero=T.Id_Prioridad_Turnero ),"") as Icono
		 FROM Turno_Activo T WHERE T.Id_Turneros= '.$id.' AND T.Estado="Espera" Order BY T.Hora_Turno ASC,T.Id_Turno_Activo ASC ';
	}else{
		$query='SELECT *,IFNULL((SELECT Icono FROM Prioridad_Turnero WHERE Id_Prioridad_Turnero=T.Id_Prioridad_Turnero ),"") as Icono
		 FROM Turno_Activo T WHERE T.Id_Turneros= '.$id.' AND T.Estado="Auditoria" Order BY T.Hora_Turno ASC,T.Id_Turno_Activo ASC  ';
	}



	return $query;
}




?>