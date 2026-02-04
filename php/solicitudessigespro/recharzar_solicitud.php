<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	//require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.configuracion.php');
	include_once('../../class/class.http_response.php');

	$http_response = new HttpResponse();
	$queryObj = new QueryBaseDatos();
	$response = array();

	$id_solicitud = ( isset( $_REQUEST['id_solicitud'] ) ? $_REQUEST['id_solicitud'] : '' );
	$funcionario_actualiza = ( isset( $_REQUEST['funcionario_actualiza'] ) ? $_REQUEST['funcionario_actualiza'] : '' );

	// var_dump($fecha_aprobacion_corvus);
	//var_dump($modelo);
	// var_dump($_FILES);

    ActualizarSolicitud($id_solicitud);
    GuardarActividadSolicitud($id_solicitud, $funcionario_actualiza, "SS-00".$id_solicitud);

    $http_response->SetRespuesta(0, 'Actualizacion Exitosa', 'Se ha rechazado la solicitud exitosamente!');
    $response = $http_response->GetRespuesta();

    unset($queryObj);
    unset($http_response);

	echo json_encode($response);

	function ActualizarSolicitud($id_solicitud){
		global $queryObj;

		$fecha_aprobacion = date('Y-m-d H:i:s');
		$query = 'UPDATE Solicitud_Sigespro SET Estado_Solicitud = "Rechazada" WHERE Id_Solicitud_Sigespro = '.$id_solicitud;
		$queryObj->SetQuery($query);
		$queryObj->QueryUpdate();
	}

	function GuardarActividadSolicitud($idSolicitud, $idFuncionario, $codigo){
			
		$oItem= new complex("Actividad_Solicitud_Sigespro","Id_Actividad_Solicitud_Sigespro");
	    $oItem->Id_Funcionario = $idFuncionario;
	    $oItem->Id_Solicitud_Sigespro = $idSolicitud;
	    $oItem->Detalle = "ProH rechazo la solicitud con codigo ".$codigo;
	    $oItem->Tipo_Actividad = 'Rechazo';
	    $oItem->save();
	    unset($oItem);
	}
?>