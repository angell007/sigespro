<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.paginacion.php');
	include_once('../../class/class.http_response.php');

	$match = ( isset( $_REQUEST['coincidencia'] ) ? $_REQUEST['coincidencia'] : '' );
	$tipo = ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : '' );

	$http_response = new HttpResponse();

	if ($tipo == 'pcga') {
		$query = '
		SELECT 
			Id_Plan_Cuentas,
			Codigo,
			Nombre,
			CONCAT(Codigo," - ", Nombre) AS Nombre_Cuenta
		FROM Plan_Cuentas
		WHERE  
			Nombre LIKE "%'.$match.'%" OR Codigo LIKE "%'.$match.'%" AND Movimiento = "S"
		ORDER BY Nombre ASC'; 
	} else {
		$query = '
		SELECT 
			Id_Plan_Cuentas,
			Codigo_Niif,
			Nombre_Niif,
			CONCAT(Codigo_Niif," - ", Nombre_Niif) AS Nombre_Cuenta_Niif
		FROM Plan_Cuentas
		WHERE  
			Nombre_Niif LIKE "%'.$match.'%" OR Codigo_Niif LIKE "%'.$match.'%" AND Movimiento = "S"
		ORDER BY Nombre_Niif ASC'; 
	}



	//Se crea la instancia que contiene la consulta a realizar
    $queryObj = new QueryBaseDatos($query);

    //Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
    $CodigoCIEs = $queryObj->ExecuteQuery('Multiple');

	echo json_encode($CodigoCIEs);
?>