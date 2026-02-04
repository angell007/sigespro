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
	//$condicion = SetCondiciones($_REQUEST);

	$anio = date('Y');

	$query = '
		SELECT 
			A.*,
			C.Nombre AS Cliente,
			CONCAT_WS(" ", F.Nombres, F.Apellidos) AS Nombre_Funcionario,
			TA.Nombre AS Tipo, F.Imagen
		FROM Actividad A
		LEFT JOIN Cliente C ON A.Id_Cliente = C.Id_Cliente
		INNER JOIN Funcionario F ON A.Identificacion_Funcionario = F.Identificacion_Funcionario
		INNER JOIN Tipo_Actividad TA ON A.Id_Tipo_Actividad = TA.Id_Tipo_Actividad
		WHERE
			A.Estado = "Pendiente"
		ORDER BY Fecha_Inicio DESC';

	//Se crea la instancia que contiene la consulta a realizar
    $queryObj = new QueryBaseDatos($query);

    //Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
    $actividades_mes = $queryObj->Consultar('Multiple');

    unset($queryObj);

	echo json_encode($actividades_mes);

?>