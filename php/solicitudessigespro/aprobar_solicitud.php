<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	//require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.configuracion.php');
	include_once('../../class/class.http_response.php');
	include_once(__DIR__ . '/permisos_sigespro.php');

	$http_response = new HttpResponse();
	$queryObj = new QueryBaseDatos();
	$response = array();

	$id_solicitud = ( isset( $_REQUEST['id_solicitud'] ) ? $_REQUEST['id_solicitud'] : '' );
	$funcionario_actualiza = ( isset( $_REQUEST['funcionario_actualiza'] ) ? $_REQUEST['funcionario_actualiza'] : '' );

	$permiso = ObtenerPermisoModulo($funcionario_actualiza, 'Solicitudes Sigespro - Gerencia');
	if (!ValidarPermiso($permiso, 'Editar')) {
		echo json_encode(RespuestaPermisoDenegado());
		exit;
	}

	$estado_actual = ObtenerEstadoSolicitud($id_solicitud);
	if ($estado_actual != 'Recibida') {
		echo json_encode(RespuestaEstadoInvalido('La solicitud debe estar en estado Recibida para aprobarse.'));
		exit;
	}

	// var_dump($fecha_aprobacion_corvus);
	//var_dump($modelo);
	// var_dump($_FILES);

    ActualizarSolicitud($id_solicitud);
    GuardarActividadSolicitud($id_solicitud, $funcionario_actualiza, "SS-00".$id_solicitud);

	$funcionario_solicita = ObtenerFuncionarioSolicita($id_solicitud);
	GuardarNotificacion($funcionario_solicita, 'Su solicitud SS-00'.$id_solicitud.' ha sido Aprobada.');

    $http_response->SetRespuesta(0, 'Actualizacion Exitosa', 'Se ha aprobado la solicitud exitosamente!');
    $response = $http_response->GetRespuesta();

    unset($queryObj);
    unset($http_response);

	echo json_encode($response);

	function ActualizarSolicitud($id_solicitud){
		global $queryObj;

		$fecha_aprobacion = date('Y-m-d H:i:s');
		$query = 'UPDATE Solicitud_Sigespro SET Estado_Solicitud = "Aprobada", Aprobacion_Proh = "'.$fecha_aprobacion.'" WHERE Id_Solicitud_Sigespro = '.$id_solicitud;
		$queryObj->SetQuery($query);
		$queryObj->QueryUpdate();
	}

	function GuardarActividadSolicitud($idSolicitud, $idFuncionario, $codigo){
			
		$oItem= new complex("Actividad_Solicitud_Sigespro","Id_Actividad_Solicitud_Sigespro");
	    $oItem->Id_Funcionario = $idFuncionario;
	    $oItem->Id_Solicitud_Sigespro = $idSolicitud;
	    $oItem->Detalle = "ProH aprobo la solicitud con codigo ".$codigo;
	    $oItem->Tipo_Actividad = 'Aprobacion Proh';
	    $oItem->save();
	    unset($oItem);
	}

	function ObtenerFuncionarioSolicita($id_solicitud){
		global $queryObj;
		$query = 'SELECT Identificacion_Funcionario_Solicita FROM Solicitud_Sigespro WHERE Id_Solicitud_Sigespro = '.$id_solicitud;
		$queryObj->SetQuery($query);
		$resultado = $queryObj->ExecuteQuery('simple');
		return $resultado['Identificacion_Funcionario_Solicita'];
	}

	function GuardarNotificacion($idFuncionario, $detalle){
		$oItem = new complex('Alerta','Id_Alerta');
		$oItem->Identificacion_Funcionario=$idFuncionario;
		$oItem->Fecha=date("Y-m-d H:i:s");
		$oItem->Tipo="Solicitud Sigespro";
		$oItem->Detalles=$detalle;
		$oItem->Respuesta="No";
		$oItem->save();
		unset($oItem);
	}
?>
