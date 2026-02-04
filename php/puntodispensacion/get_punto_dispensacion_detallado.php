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
		SELECT *, 
		(
			CASE 
				WHEN LOCATE ("1", S.Id_Servicio) THEN "Si" 
				ELSE "No" 
			END 
		) as Pos,
		(
			CASE 
				WHEN LOCATE ("2", S.Id_Servicio) THEN "Si"
				ELSE "No"  
			END 
		) as NoPos, "Si" as NoPos
		FROM Punto_Dispensacion PD 
		INNER JOIN (SELECT Id_Punto_Dispensacion, 
						GROUP_CONCAT(DISTINCT Id_Servicio ) as Id_Servicio 
					FROM Servicio_Punto_Dispensacion 
					GROUP BY Id_Punto_Dispensacion) S  ON PD.Id_Punto_Dispensacion=S.Id_Punto_Dispensacion
		WHERE PD.Id_Punto_Dispensacion = '.$id ;


	$queryObj= new QueryBaseDatos($query);
	$detalle = $queryObj->Consultar('simple');

	$detalle['servicios'] = GetServiciosPunto($id);
	$detalle['tipos_servicio'] = GetTiposServicioPunto($id);
	$detalle['Id_Turneros'] = GetTurnero($id);

	unset($queryObj);

	//var_dump ($detalle);

	echo json_encode($detalle);

	function GetServiciosPunto($idPunto){
		global $queryObj;
		$serv = array();

		$query = '
			SELECT
				SPD.Id_Servicio,
				S.Nombre
			FROM Servicio_Punto_Dispensacion SPD
			INNER JOIN Servicio S ON SPD.Id_Servicio = S.Id_Servicio
			WHERE
				SPD.Id_Punto_Dispensacion = '.$idPunto;

		$queryObj->SetQuery($query);
		$servicios = $queryObj->ExecuteQuery('multiple');

		foreach ($servicios as $key => $value) {
			array_push($serv, $value);
		}

		return $serv;
	}

	function GetTiposServicioPunto($idPunto){
		global $queryObj;
		$tipos_serv = array();

		$query = '
			SELECT
				TS.Auditoria,
				TS.Mipres,
				TS.Positiva,
				TS.Id_Servicio,
				TS.Nombre,
				TSPD.Id_Tipo_Servicio
			FROM Tipo_Servicio_Punto_Dispensacion TSPD
			INNER JOIN Tipo_Servicio TS ON TSPD.Id_Tipo_Servicio = TS.Id_Tipo_Servicio
			WHERE
				TSPD.Id_Punto_Dispensacion = '.$idPunto;

		$queryObj->SetQuery($query);
		$tipos_servicio = $queryObj->ExecuteQuery('multiple');

		foreach ($tipos_servicio as $key => $value) {
			array_push($tipos_serv, $value);
		}

		return $tipos_serv;
	}

	function GetTurnero($id){
		global $queryObj;
		$query = 'SELECT T.Id_Turneros FROM Punto_Turnero T WHERE T.Id_Punto_Dispensacion='.$id ;

		$queryObj->SetQuery($query);
		$turnero = $queryObj->ExecuteQuery('simple');	
		return $turnero['Id_Turneros'];
	}
?>