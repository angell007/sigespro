<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.http_response.php');

	$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

	/*
	$oItem = new complex($mod,"Id_".$mod,$id);
	$detalle= $oItem->getData();
	unset($oItem);*/

	$query = 'SELECT 
			*, (CASE 
		WHEN LOCATE ("1", S.Id_Servicio) THEN "Si" 
		ELSE "No" 
			END ) as Pos,  
			(CASE 
		WHEN LOCATE ("2", S.Id_Servicio) THEN "Si"
		ELSE "No"  
			END ) as NoPos
		FROM Punto_Dispensacion PD 
		LEFT JOIN (SELECT Id_Punto_Dispensacion, GROUP_CONCAT(DISTINCT Id_Servicio ) as Id_Servicio FROM Servicio_Punto_Dispensacion GROUP BY Id_Punto_Dispensacion) S  ON PD.Id_Punto_Dispensacion=S.Id_Punto_Dispensacion
		WHERE PD.Id_Punto_Dispensacion = '.$id ;


	$queryObj= new QueryBaseDatos($query);
	$detalle = $queryObj->Consultar('simple');

	$detalle['servicios'] = GetServiciosPunto($id);
	$detalle['tipos_servicio'] = GetTiposServicioPunto($id);

	unset($queryObj);

	//var_dump ($detalle);

	echo json_encode($detalle);

	function GetServiciosPunto($idPunto){
		global $queryObj;
		$serv = array();

		$query = '
			SELECT
				Id_Servicio
			FROM Servicio_Punto_Dispensacion
			WHERE
				Id_Punto_Dispensacion = '.$idPunto;

		$queryObj->SetQuery($query);
		$servicios = $queryObj->ExecuteQuery('multiple');

		foreach ($servicios as $key => $value) {
			foreach ($value as $id_servicio) {
				array_push($serv, $id_servicio);
			}
		}

		return $serv;
	}

	function GetTiposServicioPunto($idPunto){
		global $queryObj;
		$tipos_serv = array();

		$query = '
			SELECT
				Id_Tipo_Servicio
			FROM Tipo_Servicio_Punto_Dispensacion
			WHERE
				Id_Punto_Dispensacion = '.$idPunto;

		$queryObj->SetQuery($query);
		$tipos_servicio = $queryObj->ExecuteQuery('multiple');

		foreach ($tipos_servicio as $key => $value) {
			foreach ($value as $id_servicio) {
				array_push($tipos_serv, $id_servicio);
			}
		}

		return $tipos_serv;
	}
?>