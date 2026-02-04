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

$id_devolucion = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );




$condicion = SetCondiciones($_REQUEST);    
$query= GetQuery();

$queryObj->SetQuery($query);
$producto = $queryObj->ExecuteQuery('simple');


if($producto){
    $id_origen=GetOrigenDevolucion($id_devolucion);
    $lotes_existentes=GetLotes($id_origen,$producto['Id_Producto']);
    $prod[0]['Fecha_Vencimiento']='';
    $prod[0]['Lotes']=$lotes_existentes;
    $prod[0]['Cantidad']='';
    $prod[0]['Lote']='';
    $prod[0]['Precio']=0;
    $prod[0]['Id_Inventario']='';
    $prod[0]['Nombre_Comercial']=$producto['Nombre_Comercial'];
    $prod[0]['Codigo_Cum']=$producto['Codigo_Cum'];
    $prod[0]['Id_Producto']=$producto['Id_Producto'];
    $prod[0]['Id_Devolucion_Interna']=$producto['Id_Devolucion_Interna'];
    $prod[0]['Id_Producto_Devolucion_Interna']=$producto['Id_Producto_Devolucion_Interna'];
    $producto['producto']=$prod;



    $http_response->SetRespuesta(0,'Exitoso','Se obtuvieron datos de productos');
    $response=$http_response->GetRespuesta();
    $response['Producto']=$producto;
}else{
    $http_response->SetRespuesta(1,'Error','Este producto no esta asociado a esta devolucion interna, por favor revise!');
    $response=$http_response->GetRespuesta();
}
	





echo json_encode($response);

function SetCondiciones(){

	global $id_devolucion; 
    $condicion=' WHERE PD.Id_Devolucion_Interna='.$id_devolucion;

    if (isset($_REQUEST['codigo']) && $_REQUEST['codigo']) {
        
            $condicion .= " AND P.Codigo_Barras LIKE '%$_REQUEST[codigo]%'";
        
        
    }
	return $condicion; 
}

function GetQuery(){
	global $condicion;


	$query ='SELECT
	CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion," ", P.Cantidad," ", P.Unidad_Medida) as Nombre,P.Nombre_Comercial,
	P.Laboratorio_Comercial,
	P.Laboratorio_Generico,
	P.Id_Producto,
	P.Codigo_Cum,
	P.Embalaje,P.Invima,"No" as Eliminado,
    P.Cantidad_Presentacion,PD.Id_Devolucion_Interna,PD.Id_Producto_Devolucion_Interna,PD.Cantidad as Cantidad_Enviada
    
    FROM Producto_Devolucion_Interna PD INNER JOIN Producto P ON PD.Id_Producto=P.Id_Producto '.$condicion ;

	return $query;

}

function GetOrigenDevolucion($id){
    global $queryObj;
    $query="SELECT Id_Origen FROM Devolucion_Interna WHERE Id_Devolucion_Interna=$id ";
    $queryObj->SetQuery($query);
    $devolucion = $queryObj->ExecuteQuery('simple');

    return  $devolucion['Id_Origen'];

}

function GetLotes($id,$idProducto){
    global $queryObj;
    $query="SELECT Lote, Id_Inventario, Id_Producto,Cantidad as Cantidad_Disponible, Costo FROM Inventario WHERE Id_Punto_Dispensacion=$id  AND Id_Producto=$idProducto AND Cantidad>0 ";
    $queryObj->SetQuery($query);
    $lotes = $queryObj->ExecuteQuery('Multiple');

    return $lotes;
}



?>