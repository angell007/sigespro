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

    $pag = ( isset( $_REQUEST['pag'] ) ? $_REQUEST['pag'] : '' );
    $tam = ( isset( $_REQUEST['tam'] ) ? $_REQUEST['tam'] : '' );

	$condicion = SetCondiciones();

    $query = '
        SELECT 
            PCC.*,
            P.Nombre_Comercial,
            P.Codigo_Cum
        FROM Producto_Control_Cantidad PCC
        INNER JOIN Producto P ON PCC.Id_Producto = P.Id_Producto 
        '.$condicion;

    $query_count = '
        SELECT 
            COUNT(Id_Producto_Control_Cantidad) AS Total
        FROM Producto_Control_Cantidad PCC
        INNER JOIN Producto P ON PCC.Id_Producto = P.Id_Producto 
        '.$condicion;    

    $paginationData = new PaginacionData($tam, $query_count, $pag);
    $queryObj = new QueryBaseDatos($query);
	
    $productos_controlados = $queryObj->Consultar('Multiple', true, $paginationData);

    unset($http_response);
    unset($queryObj);

	echo json_encode($productos_controlados);

	function SetCondiciones(){
		global $util;

        $req = $_REQUEST;

		$condicion = '';

        if (isset($req['cum']) && $req['cum']) {
            if ($condicion != "") {
                $condicion .= " AND P.Codigo_Cum LIKE '%".$req['cum']."%'";
            } else {
                $condicion .= " WHERE P.Codigo_Cum LIKE '%".$req['cum']."%'";
            }
        }

        return $condicion;
	}


?>