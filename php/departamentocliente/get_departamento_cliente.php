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

	$id_departamento_cliente = ( isset( $_REQUEST['id_departamento_cliente'] ) ? $_REQUEST['id_departamento_cliente'] : '' );

	$query = '
		SELECT 
			DC.*,
			D.Nombre AS Nombre_Departamento
		FROM Departamento_Cliente DC		
		INNER JOIN Departamento D ON DC.Id_Departamento = D.Id_Departamento
		WHERE
			DC.Id_Departamento_Cliente ='.$id_departamento_cliente;

	$query_cliente = '
		SELECT
			C.Id_Cliente,
			C.Nombre
		FROM Departamento_Cliente DC
		INNER JOIN Cliente C ON DC.Id_Cliente = C.Id_Cliente 
		WHERE
			DC.Id_Departamento_Cliente ='.$id_departamento_cliente;

	//Se crea la instancia que contiene la consulta a realizar
    $queryObj = new QueryBaseDatos($query);

    //Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
    $response = $queryObj->Consultar('simple');

	$queryObj->setQuery($query_cliente);
	$response['Cliente'] = $queryObj->ExecuteQuery('simple');
	$response['Cliente'] = $response['Cliente'] != false ? $response['Cliente'] : '';

	unset($queryObj);

	echo json_encode($response);
?>