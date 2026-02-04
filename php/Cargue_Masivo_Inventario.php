<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	//require_once('../../config/start.inc.php');
	include_once('../../../class/class.querybasedatos.php');
	include_once('../../../class/class.paginacion.php');
	include_once('../../../class/class.http_response.php');
    include_once('../../../class/class.utility.php');

   $productos = ( isset( $_REQUEST['Productos'] ) ? $_REQUEST['Productos'] : '' );
	
	

    $prods = (array) json_decode($productos, true);

foreach($prods as $producto){


    $query = 'SELECT  PRD.Id_Producto, IFNULL(CONCAT(PRD.Nombre_Comercial," (",PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion,") ", PRD.Cantidad," ", PRD.Unidad_Medida),CONCAT(PRD.Nombre_Comercial," LAB-",PRD.Laboratorio_Comercial)) as Nombre,
    PRD.Laboratorio_Comercial,
    PRD.Laboratorio_Generico,
    PRD.Cantidad_Presentacion,
    PRD.Embalaje,
    IFNULL(PRD.Id_Categoria,0) as Id_Categoria,IFNULL((SELECT Nombre FROM Categoria WHERE Id_Categoria=PRD.Id_Categoria),"Sin Categoria") as Categoria,
    PRD.Imagen,
    PRD.Codigo_Cum,
    PRD.Codigo_Barras
    FROM Producto PRD
    LEFT JOIN Inventario_Nuevo I
    ON PRD.Id_Producto=I.Id_Producto 
    WHERE PRD.Codigo_Cum ='.$producto['Codigo_Cum']."  GROUP BY PRD.Id_Producto";

    $queryObj->SetQuery($query);
    $result = $queryObj->ExecuteQuery('simple');

}

var_dump($result);
exit(); 
?>