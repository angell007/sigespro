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
			Id_Salario,
			Nombre
		FROM Salario
			'.$condicion.' 
			ORDER BY Nombre ASC'; 

	$query_count = '
		SELECT 
			COUNT(Id_Salario) AS Total
		FROM Salario
			'.$condicion;    

	$paginationData = new PaginacionData($tam, $query_count, $pag);

	//Se crea la instancia que contiene la consulta a realizar
    $queryObj = new QueryBaseDatos($query);

    //Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
    $tipos_contrato = $queryObj->Consultar('Multiple', true, $paginationData);

	echo json_encode($tipos_contrato);

	function SetCondiciones($req){
		$condicion = '';

        if (isset($req['nombre']) && $req['nombre']) {
            if ($condicion != "") {
                $condicion .= " AND Nombre LIKE '%".$req['nombre']."%'";
            } else {
                $condicion .= " WHERE Nombre LIKE '%".$req['nombre']."%'";
            }
        }

        return $condicion;
	}
?>