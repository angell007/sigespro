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

$id_origen = ( isset( $_REQUEST['id_origen'] ) ? $_REQUEST['id_origen'] : '' );




$condicion = SetCondiciones($_REQUEST);    
$query= GetQuery();
$queryObj->SetQuery($query);
$productos = $queryObj->ExecuteQuery('Multiple');


	
$http_response->SetRespuesta(0,'Exitoso','Se obtuvieron datos de productos');
$response=$http_response->GetRespuesta();
$response['Productos']=$productos;




echo json_encode($response);

function SetCondiciones(){

	
    $condicion='';

    if (isset($_REQUEST['nombre']) && $_REQUEST['nombre'] != '') {

            $condicion .= ' AND (P.Principio_Activo LIKE "%'.$_REQUEST['nombre'].'%" OR P.Presentacion LIKE "%'.$_REQUEST['nombre'].'%" OR P.Concentracion LIKE "%'.$_REQUEST['nombre'].'%" OR P.Nombre_Comercial LIKE "%'.$_REQUEST['nombre'].'%" OR P.Cantidad LIKE "%'.$_REQUEST['nombre'].'%" OR P.Unidad_Medida LIKE "%'.$_REQUEST['nombre'].'%")';
        
        
        }

    if (isset($_REQUEST['cum']) && $_REQUEST['cum']) {
        
            $condicion .= " AND P.Codigo_Cum LIKE '%$_REQUEST[cum]%'";
        
        
    }
    if (isset($_REQUEST['lab_com']) && $_REQUEST['lab_com']) {
        
            $condicion .= " AND P.Laboratorio_Generico LIKE '%$_REQUEST[lab_com]%'";
        
        
    }

	$condicion.=" AND (P.Embalaje NOT LIKE 'MUESTRA MEDICA%' OR P.Embalaje IS NULL OR P.Embalaje='' ) ";
	

	return $condicion; 
}

function GetQuery(){
	global $condicion,$id_origen;


	$query ='SELECT
	CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion," ", P.Cantidad," ", P.Unidad_Medida) as Nombre,P.Nombre_Comercial,
	P.Laboratorio_Comercial,
	P.Laboratorio_Generico,
	P.Id_Producto,
	P.Codigo_Cum,
	P.Embalaje,
    P.Cantidad_Presentacion,
    0 as Seleccionado, 0 as Subtotal,
	SUM(I.Cantidad-I.Cantidad_Apartada) as Cantidad_Disponible, ROUND(AVG(I.Costo)) as Precio
    FROM Inventario I INNER JOIN Producto P ON I.Id_Producto=P.Id_Producto WHERE I.Id_Punto_Dispensacion='.$id_origen.$condicion .' 
    GROUP BY I.Id_Producto having Cantidad_Disponible>0';

	return $query;

}



?>