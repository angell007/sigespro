<?
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.http_response.php');

	$semana = ( isset( $_REQUEST['semana'] ) ? $_REQUEST['semana'] : '' );
	$dia = ( isset( $_REQUEST['dia'] ) ? $_REQUEST['dia'] : '' );
	$http_response = new HttpResponse();

	$query = '
		SELECT 
			Id_Cronograma_Remision
		FROM Cronograma_Remision
		WHERE
			Semana = '.$semana.' 
			AND Dia = "'.$dia.'" ';

    //Se crea la instancia que contiene la consulta a realizar
    $queryObj = new QueryBaseDatos($query);

    //Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
    $result = $queryObj->Consultar('simple');

    if ($result['query_result'] != '') {
    	
    	$query = '
			SELECT
				GROUP_CONCAT(DISTINCT PD.Id_Punto_Dispensacion) AS puntos
			FROM Punto_Cronograma_Remision PCR
			INNER JOIN Punto_Dispensacion PD ON PCR.Id_Punto = PD.Id_Punto_Dispensacion
			WHERE
				PCR.Id_Cronograma = '.$result['query_result']['Id_Cronograma_Remision']; 

		$queryObj->setQuery($query);
	    $result['query_result']['Puntos'] = $queryObj->ExecuteQuery('simple');
	    $result['query_result']['Puntos'] = StrToArray($result['query_result']['Puntos']['puntos']);
	    unset($queryobj);
    }else{

    	$http_response->SetRespuesta(0, 'Consulta Exitosa', 'No se encontraron datos!');
    	$http_response->SetDatosRespuesta(array());
		$result = $http_response->GetRespuesta();
    }

	echo json_encode($result);

	function StrToArray($str){
		$arr = explode(",", $str);
		return $arr;
	}
?>