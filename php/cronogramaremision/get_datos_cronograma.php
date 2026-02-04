<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.http_response.php');

	$http_response = new HttpResponse();

	$semana = ( isset( $_REQUEST['semana'] ) ? $_REQUEST['semana'] : '' );

	$fecha = date('Y-m-d');

	$query = '
		SELECT 
			Id_Cronograma_Remision,
			Dia
		FROM Cronograma_Remision
		WHERE
			Semana = "'.$semana.'" 
			ORDER BY Dia ASC';    

	//Se crea la instancia que contiene la consulta a realizar
    $queryObj = new QueryBaseDatos($query);

    //Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
	$cronogramas = $queryObj->Consultar('Multiple');


    $i = 0;
    foreach ($cronogramas['query_result'] as $value) {
    	
    	$puntosCronograma = GetPuntosDia($value['Id_Cronograma_Remision']);
    	$cronogramas['query_result'][$i]['Puntos'] = $puntosCronograma;
    	/*var_dump($value);
    	var_dump($puntosCronograma);*/
    	$i++;
    }


	/*$http_response->SetRespuesta(0, 'Registro Exitoso', 'Se han registrado los datos para el cronograma exitosamente!');
	$response = $http_response->GetRespuesta();*/

	echo json_encode($cronogramas);

	function GetPuntosDia($idCronograma){

		$query = '
			SELECT 
				PCR.Id_Punto_Cronograma_Remision,
				(SELECT Nombre FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion = PCR.Id_Punto) AS NombrePunto
			FROM Punto_Cronograma_Remision PCR
			WHERE
				PCR.Id_Cronograma = '.$idCronograma.' GROUP BY Id_Punto '; 

		$queryObj = new QueryBaseDatos($query);
	    $puntos = $queryObj->ExecuteQuery('Multiple');
	    unset($queryobj);

	    return $puntos;
	}
?>