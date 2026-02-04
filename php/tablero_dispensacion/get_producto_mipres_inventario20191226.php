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

$id_punto_dispensacion = ( isset( $_REQUEST['id_punto'] ) ? $_REQUEST['id_punto'] : '' );

$id_mipres = ( isset( $_REQUEST['id_mipres'] ) ? $_REQUEST['id_mipres'] : '' );

$query="SELECT Id_Producto,Cantidad,NoPrescripcion as Numero_Prescripcion  FROM Producto_Dispensacion_Mipres WHERE Id_Dispensacion_Mipres=$id_mipres";
$queryObj->SetQuery($query);
$productos_mipres = $queryObj->ExecuteQuery('Multiple');

$resultado=[];

foreach ($productos_mipres as $m) {
	
	$condicion = SetCondiciones($m['Id_Producto']);   

	$query_producto="SELECT Id_Inventario FROM Inventario WHERE Id_Punto_Dispensacion =$id_punto_dispensacion AND Id_Producto=$m[Id_Producto] AND (Cantidad-Cantidad_Apartada)>0 ";
	$queryObj->SetQuery($query_producto);
	$inv = $queryObj->ExecuteQuery('Simple');

	if($inv){
		$buscar_inventario='false';
	}else{
		$buscar_inventario='true';
	}

	$query= GetQuery($condicion,$buscar_inventario,$m['Cantidad'],$m['Numero_Prescripcion']);
	$queryObj->SetQuery($query);
	$productos = $queryObj->ExecuteQuery('Multiple');

	$resultado=array_merge($resultado,$productos);
	
}



	
$http_response->SetRespuesta(0,'Exitoso','Se obtuvieron datos de productos');
$response=$http_response->GetRespuesta();

$response['Productos']=$resultado;
$response['Total']=count($productos_mipres);




echo json_encode($response);

function SetCondiciones( $id_producto){

	$condicion=" AND P.Id_Producto= $id_producto ";

	return $condicion; 
}

function GetQuery($condicion,$buscar_inventario,$cantidad,$numeroPrescripcion){

	$query='';

	$query .='SELECT
	CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion," ", P.Cantidad," ", P.Unidad_Medida) as Nombre,P.Nombre_Comercial,
	P.Laboratorio_Comercial,
	P.Laboratorio_Generico,
	P.Id_Producto,
	P.Codigo_Cum,
	P.Embalaje,
	IFNULL((SELECT PC.Cantidad_Minima FROM Producto_Control_Cantidad PC  WHERE PC.Id_Producto=P.Id_Producto ), 0) as Cantidad_Minima, 0 as Seleccionado
	';
	
	if($buscar_inventario=='false'){
		$query .=", I.Fecha_Vencimiento, I.Lote, I.Id_Inventario, (I.Cantidad-I.Cantidad_Apartada) as Cantidad_Disponible, I.Costo, $cantidad as Cantidad_Formulada, $numeroPrescripcion as Numero_Prescripcion
		FROM Inventario I INNER JOIN Producto P ON I.Id_Producto=P.Id_Producto WHERE P.Codigo_Barras IS NOT NULL  ".$condicion .' AND (I.Cantidad-I.Cantidad_Apartada) > 0 
		ORDER BY I.Fecha_Vencimiento ASC ';
	}else{
		$query .=",0 as Cantidad_Disponible, 'Pendiente' as Lote, '0000-00-00' as Fecha_Vencimiento, 0 as Id_Inventario,0 as Costo , $cantidad as Cantidad_Formulada,$numeroPrescripcion as Numero_Prescripcion FROM Producto P WHERE P.Codigo_Barras IS NOT NULL  ".$condicion.'
		ORDER BY P.Nombre_Comercial ASC';
	}
	




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