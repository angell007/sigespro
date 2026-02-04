<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.complex.php');
	include_once('../../class/class.configuracion.php');
	include_once('../../class/class.http_response.php');
	include_once(__DIR__ . '/permisos_sigespro.php');

	$http_response = new HttpResponse();
	$response = array();

	$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );
	$id_funcionario = ( isset( $_REQUEST['id_funcionario'] ) ? $_REQUEST['id_funcionario'] : '' );
	$modelo = json_decode($modelo, true);
	$fecha = date('Y-m-d H:i:s');	
	$codigo_consecutivo = 'SS-00';

	$permiso = ObtenerPermisoModulo($id_funcionario, 'Solicitudes Sigespro - Reporte');
	if (!ValidarPermiso($permiso, 'Editar')) {
		echo json_encode(RespuestaPermisoDenegado());
		exit;
	}

    GuardarIncidencia($modelo);
    GuardarActividadSolicitud($modelo['_idSolicitud'], $modelo['_idFuncionario'], $codigo_consecutivo.$modelo['_idSolicitud']);

    $http_response->SetRespuesta(0, 'Registro Exitoso', 'Se ha registrado la incidencia exitosamente!');
    $response = $http_response->GetRespuesta();

	echo json_encode($response);

	function GuardarIncidencia($modelo){

		$oItem= new complex("Incidencia_Solicitud","Id_Incidencia_Solicitud");
	    $oItem->Id_Solicitud_Sigespro = $modelo['_idSolicitud'];
	    $oItem->Identificacion_Funcionario = $modelo['_idFuncionario'];
	    $oItem->Observacion = $modelo['_observacion'];	    
	    $oItem->save();
	    unset($oItem);
	}

	function GuardarActividadSolicitud($idSolicitud, $idFuncionario, $codigo){
			
		$oItem= new complex("Actividad_Solicitud_Sigespro","Id_Actividad_Solicitud_Sigespro");
	    $oItem->Id_Funcionario = $idFuncionario;
	    $oItem->Id_Solicitud_Sigespro = $idSolicitud;
	    $oItem->Detalle = "Se creo una incidencia para la solicitud con codigo ".$codigo;
	    $oItem->Tipo_Actividad = 'Incidencia';
	    $oItem->save();
	    unset($oItem);
	}
?>
