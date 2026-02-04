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

	$estado_actual = ObtenerEstadoSolicitud($id_solicitud);
	$permiso_gerencia = ObtenerPermisoModulo($funcionario_actualiza, 'Solicitudes Sigespro - Gerencia');
	$permiso_revision = ObtenerPermisoModulo($funcionario_actualiza, 'Solicitudes Sigespro - Revision');
	$rechazo_recibida = $estado_actual == 'Recibida' && ValidarPermiso($permiso_gerencia, 'Eliminar');
	$rechazo_realizada = $estado_actual == 'Realizada' && ValidarPermiso($permiso_revision, 'Eliminar');

	if (!$rechazo_recibida && !$rechazo_realizada) {
		echo json_encode(RespuestaPermisoDenegado());
		exit;
	}

	// var_dump($fecha_aprobacion_corvus);
	//var_dump($modelo);
	// var_dump($_FILES);

    ActualizarSolicitud($id_solicitud, $estado_actual);
    GuardarActividadSolicitud($id_solicitud, $funcionario_actualiza, "SS-00".$id_solicitud, $estado_actual);

	$funcionario_solicita = ObtenerFuncionarioSolicita($id_solicitud);
	$estado_nuevo = $estado_actual == 'Realizada' ? 'Devuelto' : 'Rechazada';
	GuardarNotificacion($funcionario_solicita, 'Su solicitud SS-00'.$id_solicitud.' ha sido '.$estado_nuevo.'.');

    if ($estado_actual == 'Realizada') {
        $http_response->SetRespuesta(0, 'Actualizacion Exitosa', 'Se ha devuelto la solicitud para ajustes!');
    } else {
        $http_response->SetRespuesta(0, 'Actualizacion Exitosa', 'Se ha rechazado la solicitud exitosamente!');
    }
    $response = $http_response->GetRespuesta();

    unset($queryObj);
    unset($http_response);

	echo json_encode($response);

	function ActualizarSolicitud($id_solicitud, $estado_actual){
		global $queryObj;

		$estado_nuevo = $estado_actual == 'Realizada' ? 'Devuelto' : 'Rechazada';
		$fecha_actualizacion = date('Y-m-d H:i:s');
		$fecha_fin = $estado_actual == 'Realizada' ? 'NULL' : 'Fecha_Fin_Labor';
		$query = 'UPDATE Solicitud_Sigespro SET Estado_Solicitud = "'.$estado_nuevo.'", Fecha_Fin_Labor = '.$fecha_fin.' WHERE Id_Solicitud_Sigespro = '.$id_solicitud;
		$queryObj->SetQuery($query);
		$queryObj->QueryUpdate();
	}

	function GuardarActividadSolicitud($idSolicitud, $idFuncionario, $codigo, $estado_actual){
			
		$detalle = $estado_actual == 'Realizada' ?
			"Revision devolvio la solicitud con codigo ".$codigo :
			"Gerencia rechazo la solicitud con codigo ".$codigo;
		$tipo = $estado_actual == 'Realizada' ? 'Devolucion' : 'Rechazo';

		$oItem= new complex("Actividad_Solicitud_Sigespro","Id_Actividad_Solicitud_Sigespro");
	    $oItem->Id_Funcionario = $idFuncionario;
	    $oItem->Id_Solicitud_Sigespro = $idSolicitud;
	    $oItem->Detalle = $detalle;
	    $oItem->Tipo_Actividad = $tipo;
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
