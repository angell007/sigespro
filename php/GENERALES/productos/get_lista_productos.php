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

	$inclusion_excluidos = ( isset( $_REQUEST['inclusion'] ) ? $_REQUEST['inclusion'] : false );
    $tipo = ( isset( $_REQUEST['tipo_consulta'] ) ? $_REQUEST['tipo_consulta'] : false );

    $queryObj = new QueryBaseDatos();
	$condicion = SetCondiciones();

	$fecha = date('Y-m-d');

    $in_condition = '';    
    $in = '';

    if ($inclusion_excluidos) {

        if ($tipo == 'asociado') {
            $in_condition = GetProductosExcluirAsociados();
        }else{
            $in_condition = GetProductosExcluirControlados();
        }

        if ($condicion == '') {
            $in = ' WHERE Id_Producto NOT IN('.$in_condition.') ';
        }else{
            $in = ' AND Id_Producto NOT IN('.$in_condition.') ';
        }    
    }    

    $query = '
        SELECT 
            Id_Producto,
            IFNULL(Imagen, "") AS Imagen,
            Nombre_Comercial,
            Codigo_Cum,
            Invima,
            Embalaje,
            Principio_Activo,
            Cantidad_Presentacion,
            false AS Seleccionado
        FROM Producto
        '.$condicion
        .$in
        .' LIMIT 50';

      
	
    $queryObj->SetQuery($query);
    $productos = $queryObj->Consultar('Multiple');

    unset($http_response);
    unset($queryObj);

	echo json_encode($productos);

	function SetCondiciones(){
		global $util;

        $req = $_REQUEST;

		if (isset($req['productos_excluir'])) {
			$req['productos_excluir'] =(array) json_decode($req['productos_excluir'], true);
		}

		$condicion = '';

        if (isset($req['nombre']) && $req['nombre']) {
            if ($condicion != "") {
                $condicion .= " AND Nombre_Comercial LIKE '%".$req['nombre']."%'";
            } else {
                $condicion .= " WHERE Nombre_Comercial LIKE '%".$req['nombre']."%'";
            }
        }

        if (isset($req['cum']) && $req['cum']) {
            if ($condicion != "") {
                $condicion .= " AND Codigo_Cum LIKE '%".$req['cum']."%'";
            } else {
                $condicion .= " WHERE Codigo_Cum LIKE '%".$req['cum']."%'";
            }
        }

        if (isset($req['invima']) && $req['invima']) {
            if ($condicion != "") {
                $condicion .= " AND Invima LIKE '%".$req['invima']."%'";
            } else {
                $condicion .= " WHERE Invima LIKE '%".$req['invima']."%'";
            }
        }

        // if (isset($req['productos_excluir']) && count($req['productos_excluir']) > 0) {
        // 	$in_condition = $util->ArrayToCommaSeparatedString($req['productos_excluir']);

        //     if ($condicion != "") {
        //         $condicion .= " AND Id_Producto NOT IN (".$in_condition.")";
        //     } else {
        //         $condicion .= " WHERE Id_Producto NOT IN (".$in_condition.")";
        //     }
        // }

        return $condicion;
	}

    function GetProductosExcluirAsociados(){
        global $queryObj;

        $query = '
            SELECT 
                Producto_Asociado
            FROM Producto_Asociado';

        $queryObj->SetQuery($query);
        $productos_asociados = $queryObj->ExecuteQuery('Multiple');
        $in_condition = BuildInCondition($productos_asociados);
        return $in_condition;
    }

    function BuildInCondition($productosAsociados){
        global $queryObj;

        $inf_asociados = '';

        if (count($productosAsociados) > 0) {
            foreach ($productosAsociados as $p) {
                $inf_asociados .= $p['Producto_Asociado'].", ";
            }

            $inf_asociados = trim($inf_asociados, ", ");
        }

        return $inf_asociados;
    }

    function GetProductosExcluirControlados(){
        global $queryObj;

        $query = '
            SELECT 
                GROUP_CONCAT(Id_Producto) AS Excluir
            FROM Producto_Control_Cantidad';

        $queryObj->SetQuery($query);
        $excluir_controlados = $queryObj->ExecuteQuery('simple');
        return $excluir_controlados['Excluir'];
    }


?>