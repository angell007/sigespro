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

$id_punto= ( isset( $_REQUEST['punto'] ) ? $_REQUEST['punto'] : '' );


$condicion = SetCondiciones();    
$query= GetQuery();

$queryObj->SetQuery($query);
$servicios = $queryObj->ExecuteQuery('Multiple');



echo json_encode($servicios);

function SetCondiciones(){
	global $id_punto;

	$condicion=" WHERE Id_Punto_Dispensacion=$id_punto"; 

	return $condicion; 
}

function GetQuery(){
	global $condicion;

	$query='SELECT 
			S.Id_Servicio, 
			S.Nombre ,
			S.Cantidad_Formulada
		FROM Servicio_Punto_Dispensacion SPD 
		INNER JOIN Servicio S ON SPD.Id_Servicio=S.Id_Servicio '
		.$condicion
		.' GROUP BY S.Id_Servicio Order BY Nombre DESC ';

	return $query;
}




?>