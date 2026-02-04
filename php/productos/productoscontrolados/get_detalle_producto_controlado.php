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

	$http_response = new HttpResponse();
    $queryObj = new QueryBaseDatos();
	$util = new Utility();

    $id_producto_controlado = ( isset( $_REQUEST['id_producto_controlado'] ) ? $_REQUEST['id_producto_controlado'] : '' );

    $query = '
        SELECT 
            PCC.*,
            P.Nombre_Comercial AS Nombre_Producto,
            P.Principio_Activo,
            P.Codigo_Cum
        FROM Producto_Control_Cantidad PCC
        INNER JOIN Producto P ON PCC.Id_Producto = P.Id_Producto
        WHERE
            Id_Producto_Control_Cantidad = '.$id_producto_controlado;
	
    $queryObj->SetQuery($query);
    $producto = $queryObj->ExecuteQuery('simple');

    unset($http_response);
    unset($queryObj);

	echo json_encode($producto);

?>