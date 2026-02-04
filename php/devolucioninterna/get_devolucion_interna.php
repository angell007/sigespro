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

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );




$condicion = SetCondiciones($id);    
$query= GetQuery();
$queryObj->SetQuery($query);
$productos = $queryObj->ExecuteQuery('Multiple');

$query= GetQueryEncabezado();
$queryObj->SetQuery($query);
$cabecera = $queryObj->ExecuteQuery('simple');

$cabecera['Items']=count($productos);

$response['Productos']=$productos;
$response['Cabecera']=$cabecera;




echo json_encode($response);

function SetCondiciones($id){
	
    $condicion='WHERE D.Id_Devolucion_Interna='.$id;
    return $condicion; 
}

function GetQuery(){
	global $condicion;


	$query ='SELECT
	CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion," ", P.Cantidad," ", P.Unidad_Medida) as Nombre,P.Nombre_Comercial,
	P.Laboratorio_Comercial,
	P.Id_Producto,
	P.Codigo_Cum,
	P.Embalaje,
    P.Cantidad_Presentacion,
    D.Cantidad
    FROM Producto_Devolucion_Interna D INNER JOIN Producto P ON D.Id_Producto=P.Id_Producto  '.$condicion ;

	return $query;

}

function GetQueryEncabezado(){
	global $condicion;


	$query ='SELECT D.*,F.*
    FROM Devolucion_Interna D INNER JOIN (SELECT CONCAT_WS(" ",Nombres,Apellidos) as Funcionario, Identificacion_Funcionario FROM Funcionario ) F ON D.Identificacion_Funcionario=F.Identificacion_Funcionario '.$condicion ;

	return $query;

}



?>