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

	$query = '
		SELECT 
			*
		FROM Punto_Dispensacion
		WHERE Id_Punto_Dispensacion = '.$id ;


	$queryObj= new QueryBaseDatos($query);
	$detalle = $queryObj->Consultar('simple');

	$detalle['servicios'] = GetServiciosPunto($id);
	$detalle['tipos_servicio'] = GetTiposServicioPunto($id);

	unset($queryObj);

	//var_dump ($detalle);

	echo json_encode($detalle);

	function GetServiciosPunto($idPunto){
		global $queryObj;

		$query = '
			SELECT
				Id_Servicio
			FROM Servicio_Punto_Dispensacion
			WHERE
				Id_Punto_Dispensacion = '.$idPunto;

		$queryObj->SetQuery($query);
		$servicios = $queryObj->ExecuteQuery('multiple');
		return $servicios;
	}

	function GetTiposServicioPunto($idPunto){
		global $queryObj;

		$query = '
			SELECT
				Id_Tipo_Servicio
			FROM Tipo_Servicio_Punto_Dispensacion
			WHERE
				Id_Punto_Dispensacion = '.$idPunto;

		$queryObj->SetQuery($query);
		$tipos_servicio = $queryObj->ExecuteQuery('multiple');
		return $tipos_servicio;
	}
?>