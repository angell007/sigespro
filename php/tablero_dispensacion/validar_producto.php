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

$id_producto = ( isset( $_REQUEST['id_producto'] ) ? $_REQUEST['id_producto'] : '' );
$id_punto = ( isset( $_REQUEST['id_punto'] ) ? $_REQUEST['id_punto'] : '' );
$eps = ( isset( $_REQUEST['eps'] ) ? $_REQUEST['eps'] : '' );
$id_tipo_servicio = ( isset( $_REQUEST['id_tipo_servicio'] ) ? $_REQUEST['id_tipo_servicio'] : '' );

$tabla=GetTabla($id_tipo_servicio);
$lista=GetListaDepartamento($id_punto);

$condicion = SetCondiciones($_REQUEST);    
$query= GetQuery();



$queryObj->SetQuery($query);

$producto = $queryObj->ExecuteQuery('simple');

if($tabla=='No_Aplica'){
	$http_response->SetRespuesta(0,'Exitoso','Se obtuvieron datos de productos');
	$response=$http_response->GetRespuesta();
}else{
	/* $cum=explode('-',$producto['Codigo_Cum']);

	if($tabla!='Producto_NoPos'){
		$query="SELECT * FROM $tabla WHERE Codigo_Cum LIKE '$cum[0]%' AND Nit_EPS=$eps";
		
		$queryObj->SetQuery($query);
		$prod = $queryObj->ExecuteQuery('simple');
	}else{
		$query="SELECT * FROM $tabla WHERE Cum LIKE '$cum[0]%' AND 	Id_Lista_Producto_Nopos=$lista";
				
		$queryObj->SetQuery($query);
		$prod = $queryObj->ExecuteQuery('simple');
	}

	if($prod){
		$http_response->SetRespuesta(0,'Exitoso','Se obtuvieron datos de productos');
		$response=$http_response->GetRespuesta();
		$response['Costo']=$prod['Precio'];
	}else{
		$http_response->SetRespuesta(1,'Error','Este producto no se puede entregar debido a que no esta contratado por la Eps');
		$response=$http_response->GetRespuesta();
	} */
	
}

$http_response->SetRespuesta(0,'Exitoso','Se obtuvieron datos de productos');
$response=$http_response->GetRespuesta();


echo json_encode($response);

function SetCondiciones(){
	global $id_producto;
	$condicion=" WHERE P.Id_Producto=$id_producto";
	

	return $condicion; 
}

function GetQuery(){
	global $condicion;
	$query ='SELECT Codigo_Cum FROM Producto P '.$condicion;

	return $query;
}

function GetTabla($id){
	global $queryObj;
	$query="SELECT Tipo_Lista FROM Tipo_Servicio WHERE Id_Tipo_Servicio=$id";
	$queryObj->SetQuery($query);
	$lista = $queryObj->ExecuteQuery('simple');

	return $lista['Tipo_Lista'];
}

function GetListaDepartamento($id){
	global $queryObj;

	$query="SELECT Id_Lista_Producto_Nopos FROM Punto_Dispensacion PT INNER JOIN Departamento_Lista_Nopos DL ON PT.Departamento=DL.Id_Departamento WHERE PT.Id_Punto_Dispensacion=$id";
	$queryObj->SetQuery($query);
	$lista = $queryObj->ExecuteQuery('simple');
	return $lista['Id_Lista_Producto_Nopos'];
}
  





?>