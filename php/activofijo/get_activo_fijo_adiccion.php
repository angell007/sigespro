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

	$id_activo = ( isset( $_REQUEST['id_activo'] ) ? $_REQUEST['id_activo'] : '' );

	$query = '
		SELECT 
			AF.Codigo, (SELECT CONCAT_WS(" ",Codigo," - ", Nombre)FROM Centro_Costo WHERE Id_Centro_Costo=AF.Id_Centro_Costo) as Centro_Costo, (SELECT Nombre_Tipo_Activo FROM Tipo_Activo_Fijo WHERE Id_Tipo_Activo_Fijo=AF.Id_Tipo_Activo_Fijo) as Tipo_Activo, AF.Id_Activo_Fijo,Id_Centro_Costo,Id_Tipo_Activo_Fijo
		FROM Activo_Fijo AF
		WHERE
			AF.Id_Activo_Fijo ='.$id_activo;

	//Se crea la instancia que contiene la consulta a realizar
    $queryObj = new QueryBaseDatos($query);

	//Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
	$activo=$queryObj->ExecuteQuery('simple');
   


	echo json_encode($activo);


?>