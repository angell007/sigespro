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

	// var_dump($_REQUEST);
	// exit;
	$condicion = SetCondiciones($_REQUEST);

	$fecha = date('Y-m-d');

    $query = '
        SELECT 
            Id_Producto,
            IFNULL(Imagen, "") AS Imagen,
            Nombre_Comercial,
            Laboratorio_Comercial,
            IFNULL(Laboratorio_Generico, "No Registrado") AS Laboratorio_Generico,
            Embalaje,
            Unidad_Empaque,
            false AS Seleccionado
        FROM Producto
        '.$condicion
        .' LIMIT 200';
	
    $queryObj = new QueryBaseDatos($query);
    $productos = $queryObj->Consultar('Multiple');

    unset($http_response);
    unset($queryObj);

	echo json_encode($productos);

	function SetCondiciones($req){
		global $util;

		if (isset($req['productos_excluir'])) {
			$req['productos_excluir'] =(array) json_decode($req['productos_excluir'], true);
		}

		$condicion = ' WHERE Id_Categoria = 6 ';

        if (isset($req['nombre']) && $req['nombre']) {
            if ($condicion != "") {
                $condicion .= " AND Nombre_Comercial LIKE '%".$req['nombre']."%'";
            } else {
                $condicion .= " WHERE Nombre_Comercial LIKE '%".$req['nombre']."%'";
            }
        }

        if (isset($req['lab_com']) && $req['lab_com']) {
            if ($condicion != "") {
                $condicion .= " AND Laboratorio_Comercial LIKE '%".$req['lab_com']."%'";
            } else {
                $condicion .= " WHERE Laboratorio_Comercial LIKE '%".$req['lab_com']."%'";
            }
        }

        if (isset($req['lab_gen']) && $req['lab_gen']) {
            if ($condicion != "") {
                $condicion .= " AND Laboratorio_Generico LIKE '%".$req['lab_gen']."%'";
            } else {
                $condicion .= " WHERE Laboratorio_Generico LIKE '%".$req['lab_gen']."%'";
            }
        }

        if (isset($req['productos_excluir']) && count($req['productos_excluir']) > 0) {
        	$in_condition = $util->ArrayToCommaSeparatedString($req['productos_excluir']);

            if ($condicion != "") {
                $condicion .= " AND Id_Producto NOT IN (".$in_condition.")";
            } else {
                $condicion .= " WHERE Id_Producto NOT IN (".$in_condition.")";
            }
        }

        return $condicion;
	}


?>