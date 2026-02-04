<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.complex.php');
	include_once('../../class/class.configuracion.php');
	include_once('../../class/class.http_response.php');
	require('../../class/class.guardar_archivos.php');
	include_once(__DIR__ . '/permisos_sigespro.php');

	//Objeto de la clase que almacena los archivos    
	$storer = new FileStorer();

	$http_response = new HttpResponse();
	$response = array();
	
/* 	$http_response->SetRespuesta(1, 'Intente Nuevamente', 'No se ha podido generar la solicitud por favor intente nuevamente');
    $response = $http_response->GetRespuesta();

	echo json_encode($response);
	exit; */

	$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );
	$modelo = json_decode($modelo, true); 
	$id_crea = isset($modelo['_identificacionFuncionarioCrea']) ? $modelo['_identificacionFuncionarioCrea'] : '';
	$permiso = ObtenerPermisoModulo($id_crea, 'Solicitudes Sigespro - Reporte');
	if (!ValidarPermiso($permiso, 'Crear')) {
		echo json_encode(RespuestaPermisoDenegado());
		exit;
	}
	$modelo['Aprobacion_Corvus'] = '';
	$modelo['Aprobacion_Proh'] = '';
	$codigo_consecutivo = 'SS-00';

	// var_dump($fecha_aprobacion_corvus);
	//var_dump($modelo);
	// var_dump($_FILES);


    $id_solicitud = GuardarSolicitud($modelo, $codigo_adjuntar);
    GuardarActividadSolicitud($id_solicitud, $modelo['_identificacionFuncionarioCrea'], $codigo_consecutivo.$id_solicitud);
	GuardarNotificacion('1036599165', 'Se ha generado una nueva solicitud con código ' . $codigo_consecutivo.$id_solicitud);

    $http_response->SetRespuesta(0, 'Registro Exitoso', 'Se ha registrado la solicitud exitosamente!');
    $response = $http_response->GetRespuesta();

	echo json_encode($response);

	function GuardarSolicitud($modelo, $codigo){
		global $MY_FILE, $storer;

		$oItem= new complex("Solicitud_Sigespro","Id_Solicitud_Sigespro");
	    $oItem->Identificacion_Funcionario_Solicita = $modelo['_identificacionFuncionarioSolicita'];
	    $oItem->Identificacion_Funcionario_Crea =$modelo['_identificacionFuncionarioCrea'];
	    $oItem->Area_Sistema =$modelo['_areaSistema'];
	    $oItem->Modulo_Sistema =$modelo['_moduloSistema'];
	    $oItem->Observacion =$modelo['_observacion'];
	    $oItem->Tipo_Solicitud =$modelo['_tipoSolicitud'];
	    $oItem->Aprobacion_Corvus = $modelo['Aprobacion_Corvus'];
	    $oItem->Aprobacion_Proh = $modelo['Aprobacion_Proh'];
	    $oItem->Estado_Solicitud = "Recibida";
	    if (count($_FILES) > 0){
		    //GUARDAR ARCHIVO Y RETORNAR NOMBRE DEL MISMO
		    $nombre_archivo = $storer->UploadFileToRemoteServer($_FILES, 'store_remote_files', 'ARCHIVOS/SOLICITUDES_SIGESPRO/');
		    
			if (is_string($nombre_archivo)) {
				$nombre_archivo = json_decode($nombre_archivo);
			}

			if (is_array($nombre_archivo) && count($nombre_archivo) > 0) {
				$oItem->Archivo_Adjunto = $nombre_archivo[0];
			}

	        // $posicion1 = strrpos($_FILES["archivo_adjunto"]['name'],'.');
	        // $extension1 =  substr($_FILES["archivo_adjunto"]['name'],$posicion1);
	        // $extension1 =  strtolower($extension1);
	        // $_filename1 = uniqid().$extension1;
	        // $_file1 = $MY_FILE . "ARCHIVOS/SOLICITUDES_SIGESPRO/" . $_filename1;	        
	        // $subido1 = move_uploaded_file($_FILES["archivo_adjunto"]['tmp_name'], $_file1);
         //    if ($subido1){
         //        @chmod ( $_file1, 0777 );
         //        $oItem->Archivo_Adjunto = $_filename1;
         //    } 
	    }
	    $oItem->save();
	    $id_solicitud = $oItem->getId();
	    unset($oItem);

	    return $codigo.$id_solicitud;
	}

	function GuardarActividadSolicitud($idSolicitud, $idFuncionario, $codigo){
			
		$oItem= new complex("Actividad_Solicitud_Sigespro","Id_Actividad_Solicitud_Sigespro");
	    $oItem->Id_Funcionario = $idFuncionario;
	    $oItem->Id_Solicitud_Sigespro = $idSolicitud;
	    $oItem->Detalle = "Se creo la solicitud con codigo ".$codigo;
	    $oItem->Tipo_Actividad = 'Creacion';
	    $oItem->save();
	    unset($oItem);

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
