<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.paginacion.php');
	include_once('../../class/class.http_response.php');

	$http_response = new HttpResponse();

	$pag = ( isset( $_REQUEST['pag'] ) ? $_REQUEST['pag'] : '' );
	$tam = ( isset( $_REQUEST['tam'] ) ? $_REQUEST['tam'] : '' );

	$condicion = SetCondiciones($_REQUEST);

	$fecha = date('Y-m-d');

	$query = '
		SELECT 
			AF.*,
            TAF.Nombre_Tipo_Activo AS Tipo_Activo,(SELECT Nombre FROM Centro_Costo WHERE ID_Centro_Costo=AF.Id_Centro_Costo) as Centro_Costo
		FROM Activo_Fijo AF
        INNER JOIN Tipo_Activo_Fijo TAF ON AF.Id_Tipo_Activo_Fijo = TAF.Id_Tipo_Activo_Fijo
		'.$condicion.' 
		ORDER BY Id_Activo_Fijo DESC';

	$query_count = '
		SELECT 
            COUNT(AF.Id_Activo_Fijo) AS Total
        FROM Activo_Fijo AF
        '.$condicion;    

	$paginationData = new PaginacionData($tam, $query_count, $pag);

	//Se crea la instancia que contiene la consulta a realizar
    $queryObj = new QueryBaseDatos($query);

    //Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
    $tipo_activos = $queryObj->Consultar('Multiple', true, $paginationData);

	echo json_encode($tipo_activos);

	function SetCondiciones($req){
		$condicion = '';

        if (isset($req['codigo']) && $req['codigo']) {
            if ($condicion != "") {
                $condicion .= " AND Codigo LIKE '%".$req['codigo']."%'";
            } else {
                $condicion .= " WHERE Codigo LIKE '%".$req['codigo']."%'";
            }
        }

        if (isset($req['nombre']) && $req['nombre']) {
            if ($condicion != "") {
                $condicion .= " AND Nombre LIKE '%".$req['nombre']."%'";
            } else {
                $condicion .= " WHERE Nombre LIKE '%".$req['nombre']."%'";
            }
        }

        if (isset($req['tipo']) && $req['tipo']) {
            if ($condicion != "") {
                $condicion .= " AND AF.Id_Tipo_Activo_Fijo = ".$req['tipo'];
            } else {
                $condicion .= " WHERE AF.Id_Tipo_Activo_Fijo = ".$req['tipo'];
            }
        }

        if (isset($req['costo_niif']) && $req['costo_niif']) {
            if ($condicion != "") {
                $condicion .= " AND Costo_NIIF = ".$req['costo_niif'];
            } else {
                $condicion .= " WHERE Costo_NIIF = ".$req['costo_niif'];
            }
        }

        if (isset($req['costo_pcga']) && $req['costo_pcga']) {
            if ($condicion != "") {
                $condicion .= " AND Costo_PCGA = ".$req['costo_pcga'];
            } else {
                $condicion .= " WHERE Costo_PCGA = ".$req['costo_pcga'];
            }
        }

        return $condicion;
	}
?>