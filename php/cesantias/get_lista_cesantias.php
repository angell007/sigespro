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

	$query = '
		SELECT 
            C.*,
            CONCAT_WS(" ", F.Nombres, F.Apellidos) AS Nombre_Funcionario,
            F.Imagen
		FROM Cesantia C
        INNER JOIN Funcionario F ON C.Identificacion_Funcionario = F.Identificacion_Funcionario
        ORDER BY C.Fecha DESC';
        
	$query_count = '
		SELECT 
            COUNT(*) AS Total
        FROM Cesantia C
        INNER JOIN Funcionario F ON C.Identificacion_Funcionario = F.Identificacion_Funcionario';

	$paginationData = new PaginacionData($tam, $query_count, $pag);

	//Se crea la instancia que contiene la consulta a realizar
    $queryObj = new QueryBaseDatos($query);

    //Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
    $cesantias = $queryObj->Consultar('Multiple', true, $paginationData);

	echo json_encode($cesantias);
?>