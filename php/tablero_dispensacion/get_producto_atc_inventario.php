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
$id_producto = ( isset( $_REQUEST['id_producto'] ) ? $_REQUEST['id_producto'] : '' );
$id_producto_dispensacion = ( isset( $_REQUEST['id_producto_dispensacion'] ) ? $_REQUEST['id_producto_dispensacion'] : '' );
$id_dispensacion = ( isset( $_REQUEST['id_dispensacion'] ) ? $_REQUEST['id_dispensacion'] : '' );
$cantidad_formulada = ( isset( $_REQUEST['cantidad_formulada'] ) ? $_REQUEST['cantidad_formulada'] : '' );
$cantidad_pendiente = ( isset( $_REQUEST['cantidad_pendiente'] ) ? $_REQUEST['cantidad_pendiente'] : '' );
$codigo = ( isset( $_REQUEST['codigo'] ) ? $_REQUEST['codigo'] : '' );


$producto=GetProducto($id_producto);

$condicion = SetCondiciones();    
$query= GetQuery();

$queryObj->SetQuery($query);
$productos = $queryObj->ExecuteQuery('Multiple');


	
$http_response->SetRespuesta(0,'Exitoso','Se obtuvieron datos de productos');
$response=$http_response->GetRespuesta();
$response['Productos']=$productos;




echo json_encode($response);

function SetCondiciones(){
	global $id_punto_dispensacion, $producto;

		$condicion=" AND I.Id_Punto_Dispensacion= $id_punto_dispensacion ";
	


		if (isset($_REQUEST['nombre']) && $_REQUEST['nombre'] != '') {

				$condicion .= ' AND (CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion," ", P.Cantidad," ", P.Unidad_Medida)  LIKE "%'.$_REQUEST['nombre'].'%" OR P.Nombre_Comercial LIKE "%'.$_REQUEST['nombre'].'%" )';
			
			
		  }

		if (isset($_REQUEST['cum']) && $_REQUEST['cum']) {
			
				$condicion .= " AND P.Codigo_Cum LIKE '%$_REQUEST[cum]%'";
			
			
		}

	$condicion.=" AND (P.Embalaje NOT LIKE 'MUESTRA MEDICA%' OR P.Embalaje IS NULL OR P.Embalaje='' ) AND P.Id_Categoria=12 AND ATC LIKE '$producto[ATC]' ";
	

	return $condicion; 
}

function GetQuery(){
	global $condicion,$id_producto,$id_producto_dispensacion,$id_dispensacion,$cantidad_formulada,$cantidad_pendiente,$codigo;

	$text=" $id_producto as Id_Producto_Antiguo, $id_producto_dispensacion as Id_Producto_Dispensacion, $id_dispensacion as Id_Dispensacion, $cantidad_pendiente as Cantidad_Pendiente, $cantidad_formulada as Cantidad_Formulada, '$codigo' as Codigo";

	$query='';

    /** Modifcado el 20 de Julio 2020 Augusto - Cambia Inventario por Inventario_Nuevo */
	$query .='SELECT
	CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion," ", P.Cantidad," ", P.Unidad_Medida) as Nombre,P.Nombre_Comercial,
	P.Laboratorio_Comercial,
	P.Laboratorio_Generico,
	P.Id_Producto,
	P.Codigo_Cum,
	P.Embalaje,

	IFNULL((SELECT PC.Cantidad_Minima FROM Producto_Control_Cantidad PC  WHERE PC.Id_Producto=P.Id_Producto ), 0) as Cantidad_Minima, 0 as Seleccionado
	, I.Fecha_Vencimiento, I.Lote, I.Id_Inventario_Nuevo, (I.Cantidad-I.Cantidad_Apartada) as Cantidad_Disponible, I.Costo, '.$text.'
		FROM Inventario_Nuevo I INNER JOIN Producto P ON I.Id_Producto=P.Id_Producto 
		WHERE P.Codigo_Barras IS NOT NULL  '.$condicion .' AND (I.Cantidad-I.Cantidad_Apartada) > 0 
		ORDER BY I.Fecha_Vencimiento ASC ';
	




	return $query;

}



function GetProducto($id){
	global $queryObj;

	$query="SELECT ATC FROM Producto WHERE Id_Producto=$id";
	$queryObj->SetQuery($query);
	$pd = $queryObj->ExecuteQuery('simple');


	return $pd;
}
  





?>