<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.http_response.php');

	$http_response = new HttpResponse();
	$queryObj = new QueryBaseDatos();
	$response = array();

	$id_radicacion = ( isset( $_REQUEST['id_radicacion'] ) ? $_REQUEST['id_radicacion'] : '' );
	$id_funcionario = ( isset( $_REQUEST['id_funcionario'] ) ? $_REQUEST['id_funcionario'] : '' );
	$tipo_servicio = ( isset( $_REQUEST['tipo_servicio'] ) ? $_REQUEST['tipo_servicio'] : '' );
	$fecha = date('Y-m-d H:i:s');


	if ($id_radicacion == '') {
		
		$http_response->SetRespuesta(1, 'Error en identificador', 'Hay una incosistencia con el identificador de la radicacion, contacte al administrador del sistema!');
		$response = $http_response->GetRespuesta();
		echo json_encode($response);
		return;
	}

	ActualizarActivdadFacturas($id_radicacion,$id_funcionario);

	$query_ids_eliminar = '
		SELECT
			R.Codigo AS Codigo_Radicado,
			IFNULL(GROUP_CONCAT(F.Codigo), "0") AS CodigoFactura,
			IFNULL(GROUP_CONCAT(RF.Id_Radicado_Factura), "0") AS Ids_Radicado_Factura,
			IFNULL(GROUP_CONCAT(GF.Id_Glosa_Factura), "0") AS Ids_Glosa_Factura,
			IFNULL(GROUP_CONCAT(RG.Id_Respuesta_Glosa), "0") AS Ids_Respuesta_Glosa
		FROM Radicado R
		INNER JOIN Radicado_Factura RF ON R.Id_Radicado = RF.Id_Radicado
		INNER JOIN Factura F ON RF.Id_Factura = F.Id_Factura
		LEFT JOIN Glosa_Factura GF ON RF.Id_Radicado_Factura = GF.Id_Radicado_Factura
		LEFT JOIN Respuesta_Glosa RG ON GF.Id_Glosa_Factura = RG.Id_Glosa_Factura
		WHERE
			R.Id_Radicado = '.$id_radicacion;

	$query_ids_facturas = '';

	if ($tipo_servicio == 'CAPITA') {
		$query_ids_facturas = '
			SELECT
				IFNULL(GROUP_CONCAT(F.Id_Factura_Capita), "0") AS Ids_Factura
			FROM Radicado R
			INNER JOIN Radicado_Factura RF ON R.Id_Radicado = RF.Id_Radicado
			INNER JOIN Factura_Capita F ON RF.Id_Factura = F.Id_Factura_Capita
			WHERE
				R.Id_Radicado = '.$id_radicacion;
	}else{

		$query_ids_facturas = '
			SELECT
				IFNULL(GROUP_CONCAT(F.Id_Factura), "0") AS Ids_Factura
			FROM Radicado R
			INNER JOIN Radicado_Factura RF ON R.Id_Radicado = RF.Id_Radicado
			INNER JOIN Factura F ON RF.Id_Factura = F.Id_Factura
			WHERE
				R.Id_Radicado = '.$id_radicacion;
	}

	

	$queryObj->SetQuery($query_ids_eliminar);
	$ids = $queryObj->ExecuteQuery('simple');


	$queryObj->SetQuery($query_ids_facturas);
	$facturas = $queryObj->ExecuteQuery('simple');

	EliminarRespuestaGlosas($ids['Ids_Respuesta_Glosa']);
	EliminarGlosasRadicacion($ids['Ids_Glosa_Factura']);
	EliminarFacturasRadicacion($id_radicacion);
	EliminarRadicacion($id_radicacion);
	ActualizarEstadoFacturas($facturas['Ids_Factura']);

	

	GuardarActividadRadicado($id_radicacion, $id_funcionario, $ids['Codigo_Radicado'],$ids["CodigoFactura"]);

	$http_response->SetRespuesta(0, 'Proceso Exitoso', 'Se ha eliminado la radicacion exitosamente!');
	$response = $http_response->GetRespuesta();

	unset($queryObj);
	unset($http_response);

	echo json_encode($response);

	function EliminarRadicacion($idRadicado){
		global $queryObj;

		// $query = 'DELETE FROM Radicado WHERE Id_Radicado = '.$idRadicado;
		$query = 'UPDATE Radicado SET Estado = "Anulada" WHERE Id_Radicado = ' .$idRadicado;
		
		$queryObj->SetQuery($query);
		$queryObj->QueryUpdate();
	}

	function EliminarFacturasRadicacion($idRadicado){
		global $queryObj;

		$query = 'DELETE FROM Radicado_Factura WHERE Id_Radicado = '.$idRadicado;
		$queryObj->SetQuery($query);
		$queryObj->QueryUpdate();
	}

	function EliminarGlosasRadicacion($idsGlosaFactura){
		global $queryObj;

		$query = 'DELETE FROM Glosa_Factura WHERE Id_Glosa_Factura IN ('.$idsGlosaFactura.')';
		$queryObj->SetQuery($query);
		$queryObj->QueryUpdate();		
	}

	function EliminarRespuestaGlosas($idsRespuestaGlosa){
		global $queryObj;

		$query = 'DELETE FROM Respuesta_Glosa WHERE Id_Respuesta_Glosa IN ('.$idsRespuestaGlosa.')';
		$queryObj->SetQuery($query);
		$queryObj->QueryUpdate();
	}

	function ActualizarEstadoFacturas($idsFacturas){
		global $queryObj, $tipo_servicio;
		$query = '';

		if ($tipo_servicio == 'CAPITA') {
			$query = 'UPDATE Factura_Capita SET Estado_Radicacion = "Pendiente" WHERE Id_Factura_Capita IN ('.$idsFacturas.')';
		}else{
			$query = 'UPDATE Factura SET Estado_Radicacion = "Pendiente" WHERE Id_Factura IN ('.$idsFacturas.')';			
		}

		$queryObj->SetQuery($query);
		$queryObj->QueryUpdate();
	}

	function GuardarActividadRadicado($idRadicado, $idFuncionario, $codigo, $facturas){
			
		$oItem= new complex("Actividad_Radicado","Id_Actividad_Radicado");
	    $oItem->Id_Funcionario = $idFuncionario;
	    $oItem->Id_Radicado = $idRadicado;
	    // $oItem->Detalle = "Se elimino la radicacion con codigo ".$codigo." y todo lo relacionado a esta!";
	    $oItem->Detalle = "Se elimino la radicacion con codigo ".$codigo." y las siguientes facturas ".$facturas."!";
	    $oItem->Estado = 'Eliminar';
	    $oItem->save();
	    unset($oItem);
	}

	function ActualizarActivdadFacturas($id_radicacion,$idFuncionario){

		
		global $queryObj;

		$query ='SELECT R.Id_Radicado, RF.Id_Factura, R.Codigo as codigoR, F.Codigo
					FROM Radicado R
					INNER JOIN Radicado_Factura RF ON R.Id_Radicado = RF.Id_Radicado
					INNER JOIN Factura F ON RF.Id_Factura = F.Id_Factura
					WHERE R.Id_Radicado = '. $id_radicacion;

		$queryObj->SetQuery($query);
		$ids = $queryObj->ExecuteQuery('Multiple');


		foreach ($ids as $i){
			
			$oItem= new complex("Actividad_Factura","Id_Actividad_Factura");
			$oItem->Id_Funcionario = $idFuncionario;
			$oItem->Id_Radicado    = $i['Id_Radicado'];
			$oItem->Factura        = $i['Id_Factura'];
			$oItem->Fecha          = date("Y-m-d H:i:s");
			$oItem->Detalle        = "Se elimina factura ". $i['Codigo'] ." del radicado ".$i['codigoR'];
			$oItem->Estado         = 'Factura Eliminada';
			$oItem->save();
			unset($oItem);

		}
	
	}

?>