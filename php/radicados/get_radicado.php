<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.http_response.php');

	$http_response = new HttpResponse();

	$id_radicado = ( isset( $_REQUEST['id_radicado'] ) ? $_REQUEST['id_radicado'] : '' );

	$query = '
		SELECT 
			R.*,
            C.Nombre AS Nombre_Cliente,
            (CASE
                WHEN R.Id_Departamento = 0 THEN "Todos"
                ELSE D.Nombre
             END) AS Nombre_Departamento,
            RE.Nombre AS Nombre_Regimen
		FROM Radicado R
        INNER JOIN Cliente C ON R.Id_Cliente = C.Id_Cliente
        LEFT JOIN Departamento D ON R.Id_Departamento = D.Id_Departamento
        INNER JOIN Regimen RE ON R.Id_Regimen = RE.Id_Regimen
		WHERE
			R.Id_Radicado ='.$id_radicado;

	//Se crea la instancia que contiene la consulta a realizar
    $queryObj = new QueryBaseDatos($query);

    //Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
    $response = $queryObj->Consultar('simple');

	unset($queryObj);

	echo json_encode($response);
?>