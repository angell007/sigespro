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

	$desarrollador = ( isset( $_REQUEST['desarrollador'] ) ? $_REQUEST['desarrollador'] : '' );
	$id_solicitud = ( isset( $_REQUEST['id_solicitud'] ) ? $_REQUEST['id_solicitud'] : '' );
	$funcionario_actualiza = ( isset( $_REQUEST['funcionario_actualiza'] ) ? $_REQUEST['funcionario_actualiza'] : '' );

	$permiso = ObtenerPermisoModulo($funcionario_actualiza, 'Solicitudes Sigespro - Gerencia');
	if (!ValidarPermiso($permiso, 'Editar')) {
		echo json_encode(RespuestaPermisoDenegado());
		exit;
	}

	$estado_actual = ObtenerEstadoSolicitud($id_solicitud);
	if ($estado_actual != 'Aprobada') {
		echo json_encode(RespuestaEstadoInvalido('La solicitud debe estar en estado Aprobada para asignar desarrollador.'));
		exit;
	}

	// var_dump($fecha_aprobacion_corvus);
	//var_dump($modelo);
	// var_dump($_FILES);

    ActualizarSolicitud($desarrollador, $id_solicitud);
    GuardarActividadSolicitud($id_solicitud, $funcionario_actualiza, "SS-00".$id_solicitud, $desarrollador);

    $http_response->SetRespuesta(0, 'Actualizacion Exitosa', 'Se ha asignado el desarrollador a la solicitud exitosamente!');
    $response = $http_response->GetRespuesta();

    unset($queryObj);
    unset($http_response);

	echo json_encode($response);

	function ActualizarSolicitud($desarrollador, $id_solicitud){
		global $queryObj;

		$fecha_asignacion = date('Y-m-d H:i:s');
		$query = 'UPDATE Solicitud_Sigespro SET Desarrollador_Asignado = "'.$desarrollador.'", Fecha_Asignacion_Desarrollador = "'.$fecha_asignacion.'" WHERE Id_Solicitud_Sigespro = '.$id_solicitud;

		$queryObj->SetQuery($query);
		$queryObj->QueryUpdate();
	}

	function GuardarActividadSolicitud($idSolicitud, $idFuncionario, $codigo, $desarrollador){
			
		$oItem= new complex("Actividad_Solicitud_Sigespro","Id_Actividad_Solicitud_Sigespro");
	    $oItem->Id_Funcionario = $idFuncionario;
	    $oItem->Id_Solicitud_Sigespro = $idSolicitud;
	    $oItem->Detalle = "Se asigno el desarrollador ".$desarrollador." a la solicitud con codigo ".$codigo;
	    $oItem->Tipo_Actividad = 'Asignacion Desarrollador';
	    $oItem->save();
	    unset($oItem);
	}
?>
