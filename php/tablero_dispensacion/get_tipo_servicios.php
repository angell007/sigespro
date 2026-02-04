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

$serv= ( isset( $_REQUEST['serv'] ) ? $_REQUEST['serv'] : '' );
$id_punto= ( isset( $_REQUEST['id_punto'] ) ? $_REQUEST['id_punto'] : '' );

$condicion = SetCondiciones();    
$query= GetQuery();

$queryObj->SetQuery($query);
$servicios = $queryObj->ExecuteQuery('Multiple');



echo json_encode($servicios);

function SetCondiciones(){
	global $serv, $id_punto;

	$condicion=" WHERE TS.Id_Servicio=$serv AND TSPD.Id_Punto_Dispensacion = $id_punto"; 

	return $condicion; 
}

function GetQuery(){
	global $condicion;

	$query='
		SELECT 
			* 
		FROM Tipo_Servicio_Punto_Dispensacion TSPD 
		INNER JOIN Tipo_Servicio TS ON TSPD.Id_Tipo_Servicio = TS.Id_Tipo_Servicio '
		.$condicion
		.' Order BY Nombre ';

	return $query;
}




?>