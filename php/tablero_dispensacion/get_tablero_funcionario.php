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
$tablero = $queryObj->ExecuteQuery('simple');




echo json_encode($tablero['Tablero']);

function GetQuery(){
	global $id;

	$query='SELECT PE.Tablero
	FROM Perfil PE
	INNER JOIN Perfil_Funcionario PF
	ON PE.Id_Perfil=PF.Id_Perfil
	WHERE PF.Identificacion_Funcionario= '.$id;

	return $query;
}




?>