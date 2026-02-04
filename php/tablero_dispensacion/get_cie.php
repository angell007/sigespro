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

$cie = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
  
$query= GetQuery();
$queryObj->SetQuery($query);
$cie = $queryObj->ExecuteQuery('simple');


	
$http_response->SetRespuesta(0,'Exitoso','Se obtuvieron datos de productos');
$response=$http_response->GetRespuesta();
$response['Cie']=$cie['Nombre'];




echo json_encode($response);


function GetQuery(){
	global $cie;

	$query=" SELECT CONCAT(Codigo,'-',Nombre) as Nombre FROM Codigo_CIE WHERE Id_Codigo_CIE=$cie ";
	return $query;
}

