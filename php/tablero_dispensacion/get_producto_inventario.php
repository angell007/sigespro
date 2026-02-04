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
$eps = ( isset( $_REQUEST['eps'] ) ? $_REQUEST['eps'] : '' );
$id_tipo_servicio = ( isset( $_REQUEST['id_tipo_servicio'] ) ? $_REQUEST['id_tipo_servicio'] : '' );
$buscar_inventario = ( isset( $_REQUEST['inv'] ) ? $_REQUEST['inv'] : 'false' );

$id_servicio = GetTipoServicio($id_tipo_servicio);

$condicion = SetCondiciones($_REQUEST);    
$query= GetQuery();


$queryObj->SetQuery($query);
//var_dump($tipodeservicio,$eps, $query);
//exit;
$productos = $queryObj->ExecuteQuery('Multiple');

$http_response->SetRespuesta(0,'Exitoso','Se obtuvieron datos de productos');
$response=$http_response->GetRespuesta();
$response['Productos']=$productos;

echo json_encode($response);

function SetCondiciones(){
	global $id_punto_dispensacion, $buscar_inventario;
	if($buscar_inventario=='false'){
		$condicion=" AND E.Id_Punto_Dispensacion= $id_punto_dispensacion ";
	}else{
		$condicion=" AND P.Codigo_Barras IS NOT NULL ";
	}


	if (isset($_REQUEST['cod_barras']) && $_REQUEST['cod_barras'] != "") { // Si está filtrando por código de barras, solo filtraré por ese campo, si no, por el restante.	
			$condicion .= " AND P.Codigo_Barras LIKE '$_REQUEST[cod_barras]%' ";
		
	}else{

		if (isset($_REQUEST['nombre']) && $_REQUEST['nombre'] != '') {

				$condicion .= ' AND (P.Principio_Activo LIKE "%'.$_REQUEST['nombre'].'%" OR P.Presentacion LIKE "%'.$_REQUEST['nombre'].'%" OR P.Concentracion LIKE "%'.$_REQUEST['nombre'].'%" OR P.Nombre_Comercial LIKE "%'.$_REQUEST['nombre'].'%" OR P.Cantidad LIKE "%'.$_REQUEST['nombre'].'%" OR P.Unidad_Medida LIKE "%'.$_REQUEST['nombre'].'%")';
			
			
		  }

		if (isset($_REQUEST['cum']) && $_REQUEST['cum']) {
			
				$condicion .= " AND P.Codigo_Cum LIKE '%$_REQUEST[cum]%'";
			
			
		}
		if (isset($_REQUEST['lab_com']) && $_REQUEST['lab_com']) {
			
				$condicion .= " AND P.Laboratorio_Generico LIKE '%$_REQUEST[lab_com]%'";	
		}
	}

	$condicion.=" AND (P.Embalaje NOT LIKE 'MUESTRA MEDICA%' OR P.Embalaje IS NULL OR P.Embalaje='' ) ";
	
	return $condicion; 
}

function GetQuery(){
	global $condicion,$buscar_inventario,$tabla,$inner,$eps,$id_servicio,$id_tipo_servicio ;
	$brand = '';

	/* Modifcado el 07 de Julio 2021 Roberth - los tipo servicio tienen contratos 
	
		$brand = 'IF((SELECT PC.Cum AS CODE
						FROM Tipo_Servicio_Contrato TSC
						INNER JOIN Contrato C ON TSC.Id_Contrato = C.Id_Contrato
						INNER JOIN Producto_Contrato PC ON C.Id_Contrato = PC.Id_Contrato
						WHERE TSC.Id_Tipo_Servicio = '.$id_tipo_servicio.'
							AND C.Id_Cliente = '.$eps.' AND PC.Cum = P.Codigo_Cum LIMIT 1 ) <> "", True, False ) As Brand, ';	
		$query='';
	*/
	/**
	 * Modificado el  01 de Julio por Stevenson Ariza 
	 * Se realiza modificacion en la consulta por errores de subquery return more than 1 row
	 * Se realiza modificicacion en la consulta  por errores en los datos $eps es el nit no id_cliente
	 * 
	 */
	$brand = '
				CASE
					WHEN EXISTS (
						SELECT 1
						FROM Tipo_Servicio_Contrato TSC
						INNER JOIN Contrato C ON TSC.Id_Contrato = C.Id_Contrato
						INNER JOIN Producto_Contrato PC ON C.Id_Contrato = PC.Id_Contrato
						WHERE TSC.Id_Tipo_Servicio = '.$id_tipo_servicio.'
						AND C.Id_Cliente = '.$eps.' AND PC.Cum = P.Codigo_Cum LIMIT 1
					) THEN True
					WHEN EXISTS (
						SELECT 1
						FROM Tipo_Servicio_Contrato TSC
						INNER JOIN Contrato C ON TSC.Id_Contrato = C.Id_Contrato
						INNER JOIN Producto_Contrato PC ON C.Id_Contrato = PC.Id_Contrato
						WHERE TSC.Id_Tipo_Servicio = '.$id_tipo_servicio.'
						AND PC.Cum = P.Codigo_Cum LIMIT 1
					) THEN True
					ELSE False
				END As Brand, ';	
	$query='';

    /* Modifcado el 20 de Julio 2020 Augusto - Cambia Inventario por Inventario_Nuevo */
	$query .='SELECT
	CONCAT_WS(" ", P.Principio_Activo,P.Presentacion,P.Concentracion,P.Cantidad, P.Unidad_Medida) as Nombre,
	P.Nombre_Comercial,
	P.Laboratorio_Comercial,
	P.Laboratorio_Generico,
	P.Id_Producto,
	P.Codigo_Cum,
	SubC.Nombre AS Categoria,
	'.$brand.'
	P.Embalaje,
	IFNULL((SELECT PC.Cantidad_Minima FROM Producto_Control_Cantidad PC  WHERE PC.Id_Producto=P.Id_Producto LIMIT 1 ), 0) as Cantidad_Minima, 0 as Seleccionado
	';
    /*Modificado el 18-08-2020 Carlos Cardona - Costo Promedio */	
	if($buscar_inventario=='false'){
		$query .=", I.Fecha_Vencimiento, I.Lote, I.Id_Inventario_Nuevo, (I.Cantidad) as Cantidad_Disponible,
	     IFNULL( (SELECT CP.Costo_Promedio  FROM Costo_Promedio CP WHERE CP.Id_Producto = I.Id_Producto LIMIT 1) , 0  ) AS Costo
		FROM Inventario_Nuevo I 
		INNER JOIN Estiba E ON E.Id_Estiba = I.Id_Estiba
		INNER JOIN Producto P ON I.Id_Producto=P.Id_Producto 
		INNER JOIN Subcategoria SubC ON P.Id_Subcategoria = SubC.Id_Subcategoria
		WHERE I.fecha_vencimiento >= DATE_ADD(CURDATE(), INTERVAL 1 MONTH) AND P.Codigo_Barras IS NOT NULL AND P.Estado !='Inactivo' ".$condicion .' AND (I.Cantidad-I.Cantidad_Apartada) > 0 
		ORDER BY I.Fecha_Vencimiento ASC ';
	}else{
		$query .=",0 as Cantidad_Disponible, 'Pendiente' as Lote, '0000-00-00' as Fecha_Vencimiento, 0 as Id_Inventario_Nuevo,0 as Costo 
		FROM Producto P 
		INNER JOIN Subcategoria SubC ON P.Id_Subcategoria = SubC.Id_Subcategoria
		WHERE P.Codigo_Barras IS NOT NULL AND P.Estado !='Inactivo'   ".$condicion.'
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

function GetTipoServicio($id){
	global $queryObj;
	$query="SELECT  Id_Servicio FROM Tipo_Servicio WHERE Id_Tipo_Servicio=$id";
	$queryObj->SetQuery($query);
	$lista = $queryObj->ExecuteQuery('simple');
	
	return $lista['Id_Servicio'];
}
function GetListaDepartamento($id){
	global $queryObj;

	$query="SELECT Id_Lista_Producto_Nopos FROM Punto_Dispensacion PT INNER JOIN Departamento_Lista_Nopos DL ON PT.Departamento=DL.Id_Departamento WHERE PT.Id_Punto_Dispensacion=$id";
	$queryObj->SetQuery($query);
	$lista = $queryObj->ExecuteQuery('simple');
	return $lista['Id_Lista_Producto_Nopos'];
}



// function GetQuery(){
// 	global $condicion,$buscar_inventario,$tabla,$inner,$eps,$id_servicio ;
// 	$brand = '';

// 	if ($id_servicio == 2) {
// 		# code...+
// 		$brand = ' IF((SELECT PN.Cum AS CODE FROM Lista_Producto_Nopos  As LPN INNER JOIN Producto_NoPos  AS PN  ON 
// 		LPN.Id_Lista_Producto_Nopos = PN.Id_Lista_Producto_Nopos WHERE  PN.Cum = P.Codigo_Cum AND LPN.Id_Cliente = '.$eps.'  LIMIT 1) 
// 		<> "", True, False ) As Brand, ';
// 	}else{
// 		$brand =  " True AS Brand, ";
// 	}
// 	$query='';
//     /** Modifcado el 20 de Julio 2020 Augusto - Cambia Inventario por Inventario_Nuevo */
	
// 	$query .='SELECT
// 	CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion," ", P.Cantidad," ", P.Unidad_Medida) as Nombre,P.Nombre_Comercial,
// 	P.Laboratorio_Comercial,
// 	P.Laboratorio_Generico,
// 	P.Id_Producto,
// 	P.Codigo_Cum,
// 	'.$brand.'
// 	P.Embalaje,
// 	IFNULL((SELECT PC.Cantidad_Minima FROM Producto_Control_Cantidad PC  WHERE PC.Id_Producto=P.Id_Producto ), 0) as Cantidad_Minima, 0 as Seleccionado
// 	';
//     /*Modificado el 18-08-2020 Carlos Cardona - Costo Promedio */	
// 	if($buscar_inventario=='false'){
// 		$query .=", I.Fecha_Vencimiento, I.Lote, I.Id_Inventario_Nuevo, (I.Cantidad-I.Cantidad_Apartada) as Cantidad_Disponible,
// 	     IFNULL( (SELECT CP.Costo_Promedio  FROM Costo_Promedio CP WHERE CP.Id_Producto = I.Id_Producto ), 0  ) AS Costo
// 		FROM Inventario_Nuevo I INNER JOIN Producto P ON I.Id_Producto=P.Id_Producto WHERE P.Codigo_Barras IS NOT NULL AND P.Estado !='Inactivo' ".$condicion .' AND (I.Cantidad-I.Cantidad_Apartada) > 0 
// 		ORDER BY I.Fecha_Vencimiento ASC ';
// 	}else{
// 		$query .=",0 as Cantidad_Disponible, 'Pendiente' as Lote, '0000-00-00' as Fecha_Vencimiento, 0 as Id_Inventario_Nuevo,0 as Costo FROM Producto P WHERE P.Codigo_Barras IS NOT NULL AND P.Estado !='Inactivo'   ".$condicion.'
// 		ORDER BY P.Nombre_Comercial ASC';
// 	}
	
// 	return $query;

// }
  





?>
