<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.complex.php');
	include_once('../../class/class.http_response.php');
	require('../../class/class.guardar_archivos.php');

	//Objeto de la clase que almacena los archivos    
	$storer = new FileStorer();

	$http_response = new HttpResponse();
	$response = array();

	$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );
	$modelo = json_decode($modelo, true);

	$fecha = date('Y-m-d H:i:s');

	if ($modelo['Id_Actividad'] == '') {
	    $http_response->SetRespuesta(1, 'Error', 'Sin identificador de actividad!');
	    $response = $http_response->GetRespuesta();
	}else{

		$oItem= new complex("Actividad","Id_Actividad", $modelo['Id_Actividad']);
	    $oItem->Id_Funcionario_Cambio_Estado = $modelo['Id_Funcionario_Cambio_Estado'];
	    $oItem->Observaciones_Jefe = $modelo['Observacion_Jefe'];
	    //FALTA ADJUNTO
	    $oItem->Fecha_Cambio_Estado = $fecha;
	    $oItem->Estado = $modelo['Estado'];
		$oItem->save();
		$id_actividad = $oItem->getId();
	    unset($oItem);
	}
	
	if (!empty($_FILES['Archivo']['name'])){
	    //GUARDAR ARCHIVO Y RETORNAR NOMBRE DEL MISMO
	    $nombre_archivo = $storer->UploadFileToRemoteServer($_FILES, 'store_remote_files', 'ARCHIVOS/DOCUMENTOS_ACTIVIDADES/');
	    $nombre_archivo = $nombre_archivo[0];
	    
		// $posicion1 = strrpos($_FILES['Archivo']['name'],'.')+1;
		// $extension1 =  substr($_FILES['Archivo']['name'],$posicion1);
		// $extension1 =  strtolower($extension1);
		// $_filename1 = uniqid() . "." . $extension1;
		// $_file1 = $MY_FILE . "ARCHIVOS/DOCUMENTOS_ACTIVIDADES/". $_filename1;
		
		// $subido1 = move_uploaded_file($_FILES['Archivo']['tmp_name'], $_file1);
		// 	if ($subido1){		
		// 		@chmod ( $_file1, 0777 );
		// 		$nombre_archivo = $_filename1;
		// 	} 
	}
	if( $nombre_archivo){
		$oItem= new complex("Actividad","Id_Actividad", $id_actividad);
		$oItem->Adjunto=$nombre_archivo;
		$oItem->save();
		unset($oItem);
		$http_response->SetRespuesta(0, 'Registro Exitoso', 'Se ha registrado la actividad exitosamente!');
	    $response = $http_response->GetRespuesta();
	}


	echo json_encode($response);
?>