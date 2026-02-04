<?php
	ini_set('memory_limit', '2048M');
	set_time_limit(0);

	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	include_once('../../../class/class.querybasedatos.php');
	include_once('../../../class/class.paginacion.php');
	include_once('../../../class/class.http_response.php');

	$http_response = new HttpResponse();
    $productos = array();

    $query = "SELECT PA.Id_Producto_Asociado, 
	P.Codigo_Cum,
	P.Nombre_Comercial, 
		IFNULL(CONCAT_WS(' ',
			P.Principio_Activo,
			P.Presentacion,
			P.Concentracion, '(',
			P.Nombre_Comercial,')',
			P.Cantidad,
			P.Unidad_Medida
			), P.Nombre_Comercial) As Nombre_Producto, 
			If(PA.Id_Asociado_Genericos ='0' or PA.Id_Asociado_Genericos ='', '-', PA.Id_Asociado_Genericos )AS Grupo_Genericos
	FROM Producto_Asociado PA
	INNER JOIN Producto P 
	 ON PA.Asociados2 LIKE CONCAT('%-',P.Id_Producto, '-%')
	
	ORDER BY PA.Id_Producto_Asociado ASC";

    $queryObj = new QueryBaseDatos($query);	
    $productos = $queryObj->ExecuteQuery('Multiple');
   
	echo json_encode($productos);

    
?>