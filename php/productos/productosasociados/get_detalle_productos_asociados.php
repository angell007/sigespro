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

    $id_producto_asociado = ( isset( $_REQUEST['id_producto_asociado'] ) ? $_REQUEST['id_producto_asociado'] : '' );

    $query = '
        SELECT 
            *
        FROM Producto_Asociado
        WHERE
            Id_Producto_Asociado = '.$id_producto_asociado;
	
    $queryObj->SetQuery($query);
    $productos = $queryObj->ExecuteQuery('simple');
    $asociados = ObtenerInformacionProductosAsociados($productos);

    unset($http_response);
    unset($queryObj);

	echo json_encode($asociados);

    function ObtenerInformacionProductosAsociados($productoAsociado){
        global $queryObj;

        $asociados = explode(", ", $productoAsociado['Producto_Asociado']);
        $info_asociados = array();
        $inf_asociados = '';

        foreach ($asociados as $id_producto) {
            $query = '
                SELECT
                    Id_Producto,
                    Nombre_Comercial,
                    Codigo_Cum
                FROM Producto
                WHERE
                    Id_Producto = '.$id_producto;

            $queryObj->SetQuery($query);
            $producto = $queryObj->ExecuteQuery('simple');
            $info_asociados[] = $producto;
        }

        return $info_asociados;
    }


?>