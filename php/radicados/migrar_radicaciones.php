<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	// header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.consulta.php');
	include_once('../../class/class.http_response.php');

	$http_response = new HttpResponse();

	$query = "SELECT * FROM Radicacion_Temp GROUP BY Codigo_Agrupacion ORDER BY Fecha_Radicacion";

	//Se crea la instancia que contiene la consulta a realizar
    $queryObj = new QueryBaseDatos($query);

    //Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
	$response = $queryObj->Consultar('Multiple')['query_result'];
	
	foreach ($response as $i => $value) {
		$j = $i+1;
		$cons = 836+$j;
		$codigo = strlen($cons) == 3 ? "RAD00$cons" : "RAD0$cons";
		updateCodigoRadicado($value['Codigo_Agrupacion'],$codigo);
	}

	echo "Terminó";

	function updateCodigoRadicado($agrupacion, $cod) {
		$query = "UPDATE Radicacion_Temp SET Codigo = '$cod' WHERE Codigo_Agrupacion = '$agrupacion'";

		$oCon = new consulta();
		$oCon->setQuery($query);
		$oCon->createData();
		unset($oCon);
	}

	
?>