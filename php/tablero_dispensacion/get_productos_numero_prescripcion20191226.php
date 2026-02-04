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
$http_response = new HttpResponse();

$num= ( isset( $_REQUEST['num'] ) ? $_REQUEST['num'] : '' );
$idPaciente= ( isset( $_REQUEST['pac'] ) ? $_REQUEST['pac'] : '' );

$query= GetQuery();

$queryObj->SetQuery($query);
$direccionamientos = $queryObj->ExecuteQuery('Multiple');

if(count($direccionamientos)>0){
	$http_response->SetRespuesta(0, 'Se Obtuvieron datos', '');
	$response = $http_response->GetRespuesta();
	$response['Productos']=$direccionamientos;
}else{
	$http_response->SetRespuesta(1, 'No se Obtuvieron datos', '');
	$response = $http_response->GetRespuesta();
}



echo json_encode($response);



function GetQuery(){
	global $idPaciente,$num;

	$fecha=date('Y-m-d');

	$query=" SELECT  DATE_SUB(Fecha_Maxima_Entrega, INTERVAL 31 DAY) as Resta, DATE_SUB(Fecha_Maxima_Entrega, INTERVAL 7 DAY) as Maxima_Fecha,PD.Id_Dispensacion_Mipres, CONCAT(P.Principio_Activo,' ',P.Presentacion,' ',P.Concentracion,' ', P.Cantidad,' ', P.Unidad_Medida) as Nombre,P.Nombre_Comercial,
	P.Laboratorio_Comercial,
	P.Laboratorio_Generico,
	P.Id_Producto,PD.Codigo_Cum,
	P.Codigo_Cum as Cum,PD.Cantidad as Cantidad_Formulada, PD.NoPrescripcion as Numero_Prescripcion 
	FROm Dispensacion_Mipres D
	INNER JOIN Producto_Dispensacion_Mipres PD ON D.Id_Dispensacion_Mipres=PD.Id_Dispensacion_Mipres
	INNER JOIN Producto P ON PD.Id_Producto = P.Id_Producto WHERE Id_Paciente='$idPaciente' AND  PD.NoPrescripcion='$num' AND D.Estado='Pendiente'  HAVING '$fecha' >=Resta AND '$fecha'<=Maxima_Fecha   ";

	return $query;
}




?>