<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../../config/start.inc.php');
	include_once('../../../class/class.querybasedatos.php');
	include_once('../../../class/class.http_response.php');

	$http_response = new HttpResponse();

	$id_servicio = ( isset( $_REQUEST['id_servicio'] ) ? $_REQUEST['id_servicio'] : '' );

	$id_servicio = str_replace("[", "(", $id_servicio);
	$id_servicio = str_replace("]", ")", $id_servicio);

	$query = '
		SELECT 
			TS.Id_Tipo_Servicio AS value,
			CONCAT_WS(" - ", S.Nombre, TS.Nombre) AS label
		FROM Tipo_Servicio TS
		INNER JOIN Servicio S ON TS.Id_Servicio = S.Id_Servicio
		WHERE
			TS.Id_Servicio IN '.$id_servicio.'
		ORDER BY TS.Nombre ASC';

	//Se crea la instancia que contiene la consulta a realizar
    $queryObj = new QueryBaseDatos($query);

    //Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
    $tipos = $queryObj->Consultar('Multiple');

	echo json_encode($tipos);
?>