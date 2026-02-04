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

	$permiso = ObtenerPermisoModulo($funcionario_actualiza, 'Solicitudes Sigespro - Desarrollo');
	if (!ValidarPermiso($permiso, 'Editar')) {
		echo json_encode(RespuestaPermisoDenegado());
		exit;
	}

	$estado_actual = ObtenerEstadoSolicitud($id_solicitud);
	if ($estado_actual != 'En Desarrollo') {
		echo json_encode(RespuestaEstadoInvalido('La solicitud debe estar en estado En Desarrollo para finalizar.'));
		exit;
	}

	// var_dump($fecha_aprobacion_corvus);
	//var_dump($modelo);
	// var_dump($_FILES);

    ActualizarSolicitud($id_solicitud);
    GuardarActividadSolicitud($id_solicitud, $funcionario_actualiza, "SS-00".$id_solicitud);

	$funcionario_solicita = ObtenerFuncionarioSolicita($id_solicitud);
	GuardarNotificacion($funcionario_solicita, 'Su solicitud SS-00'.$id_solicitud.' ha sido Realizada, por favor validar.');

    $http_response->SetRespuesta(0, 'Actualizacion Exitosa', 'Se ha finalizado el temporizador de solucion a la solicitud!');
    $response = $http_response->GetRespuesta();

    unset($queryObj);
    unset($http_response);

	echo json_encode($response);

	function ActualizarSolicitud($id_solicitud){
		global $queryObj;

		$fecha_fin_labor = date('Y-m-d H:i:s');
		$query = 'UPDATE Solicitud_Sigespro SET Estado_Solicitud = "Realizada", Fecha_Fin_Labor = "'.$fecha_fin_labor.'" WHERE Id_Solicitud_Sigespro = '.$id_solicitud;
		$queryObj->SetQuery($query);
		$queryObj->QueryUpdate();
	}

	function GuardarActividadSolicitud($idSolicitud, $idFuncionario, $codigo){
			
		$oItem= new complex("Actividad_Solicitud_Sigespro","Id_Actividad_Solicitud_Sigespro");
	    $oItem->Id_Funcionario = $idFuncionario;
	    $oItem->Id_Solicitud_Sigespro = $idSolicitud;
	    $oItem->Detalle = "Se ha finalizado el desarrollo de la solucion para la solicitud con codigo ".$codigo;
	    $oItem->Tipo_Actividad = 'Fin Desarrollo';
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
