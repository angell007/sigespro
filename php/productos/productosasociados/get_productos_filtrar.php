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
	$util = new Utility();
    $productos = array();

    $query = '
        SELECT 
            Producto_Asociado
        FROM Producto_Asociado';

    $queryObj = new QueryBaseDatos($query);	
    $productos_asociados = $queryObj->ExecuteQuery('Multiple');
    $in_condition = BuildInCondition($productos_asociados);

    if ($in_condition != '') {

        $query_productos = '
            SELECT 
                Id_Producto as value,
                concat(Nombre_Comercial, " - ", Codigo_Cum) as label
            FROM Producto
            WHERE
                Id_Producto IN ('.$in_condition.')';

        $queryObj->SetQuery($query_productos);
        $productos = $queryObj->Consultar('Multiple');
    }

    unset($http_response);
    unset($queryObj);

	echo json_encode($productos);

    function BuildInCondition($productosAsociados){
        global $queryObj;

        $inf_asociados = '';

        foreach ($productosAsociados as $p) {
            $inf_asociados .= $p['Producto_Asociado'].", ";
        }

        return trim($inf_asociados, ", ");
    }


?>