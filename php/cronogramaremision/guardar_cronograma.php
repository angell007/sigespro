<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');


	require_once('../../config/start.inc.php');
	include_once('../../class/class.lista.php');
	include_once('../../class/class.complex.php');
	require_once('../../class/class.configuracion.php');
	include_once('../../class/class.consulta.php');
	include_once('../../class/class.http_response.php');
	include_once('../../class/class.querybasedatos.php');

	$http_response = new HttpResponse();

	$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );
	$puntos = ( isset( $_REQUEST['puntos'] ) ? $_REQUEST['puntos'] : '' );

	$modelo = json_decode($modelo, true);
	$puntos = json_decode($puntos, true);

	$fecha = date('Y-m-d');

	$query = '
		SELECT 
			Id_Cronograma_Remision 
		FROM Cronograma_Remision
		WHERE
			Dia = "'.$modelo['Dia'].'" AND Semana = "'.$modelo['Semana'].'"';     

	$queryObj = new QueryBaseDatos($query); 
	$cronograma = $queryObj->ExecuteQuery('simple');

	if ($cronograma['Id_Cronograma_Remision']) {
		
		$query = '
			SELECT
				GROUP_CONCAT(Id_Punto_Cronograma_Remision) AS puntos
			FROM Punto_Cronograma_Remision
			WHERE
				Id_Cronograma = '.$cronograma['Id_Cronograma_Remision']; 

		$queryObj->setQuery($query);
		$puntos_guardados = $queryObj->ExecuteQuery('simple');


		
		if($puntos_guardados['puntos']){
			$query_borrar = 'DELETE FROM Punto_Cronograma_Remision WHERE Id_Punto_Cronograma_Remision IN ('.$puntos_guardados['puntos'].')';
			$oCon= new consulta();
			$oCon->setQuery($query_borrar);
			$puntos_borrar = $oCon->deleteData();
			unset($oCon);
		}

	  

	    foreach ($puntos as $value) {
	    	
	    	$oItem=new complex('Punto_Cronograma_Remision',"Id_Punto_Cronograma_Remision");
		    $oItem->Id_Punto=$value;
		    $oItem->Id_Cronograma=$cronograma['Id_Cronograma_Remision'];
		    $oItem->save();
		    unset($oItem);
	    }
	    //$puntos_guardados = StrToArray($puntos_guardados['puntos']);
	    unset($queryobj);
	}else{
		
		$oItem=new complex('Cronograma_Remision',"Id_Cronograma_Remision");
	    $oItem->Dia=$modelo['Dia'];
	    $oItem->Semana=$modelo['Semana'];
	    $oItem->Fecha_Asignacion=$fecha;
	    $oItem->Id_Funcionario=$modelo['Id_Funcionario'];
	    $oItem->save();
	    $id = $oItem->getId();
	    unset($oItem);

	    foreach ($puntos as $value) {
	    	$oItem=new complex('Punto_Cronograma_Remision',"Id_Punto_Cronograma_Remision");
		    $oItem->Id_Punto=$value;
		    $oItem->Id_Cronograma=$id;
		    $oItem->save();
		    unset($oItem);
	    }
	}

	$http_response->SetRespuesta(0, 'Registro Exitoso', 'Se han registrado los datos para el cronograma exitosamente!');
	$response = $http_response->GetRespuesta();

	echo json_encode($response);

	function StrToArray($str){
		$arr = explode(",", $str);
		return $arr;
	}

?>