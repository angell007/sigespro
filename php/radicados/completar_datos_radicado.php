<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.complex.php');
	include_once('../../class/class.configuracion.php');
	include_once('../../class/class.http_response.php');

	$http_response = new HttpResponse();
	$response = array();

	$archivo = isset($_FILES['archivo']) ? $_FILES['archivo'] : false;

	$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '');
	$modelo = json_decode($modelo, true);

	
	$file_path = __DIR__.'/../../ARCHIVOS/RADICADOS';
	$extensions = array("jpg","png","jpeg","pdf");
	try {
		//code...


		$oItem= new complex("Radicado", "Id_Radicado", $modelo['Id_Radicado']);

		if ($oItem->Consecutivo == '') {
			if ($modelo['Consecutivo'] != '') {
				$oItem->Consecutivo = $modelo['Consecutivo'];
			}
		}
	
		if ($oItem->Numero_Radicado == '') {
			if ($modelo['Numero_Radicado'] != '') {
				$oItem->Numero_Radicado = $modelo['Numero_Radicado'];
			}
		}
	
		if ($oItem->Fecha_Radicado == '') {
			if ($modelo['Fecha_Radicado'] != '') {
				$oItem->Fecha_Radicado = $modelo['Fecha_Radicado'];
			}
		}
		
		if ($oItem->Consecutivo != '' && $oItem->Numero_Radicado != '' && $oItem->Fecha_Radicado != '') {
			
			$oItem->Estado = 'Radicada';
		}
		validarArchivo();
		$temp_archivo = $archivo['tmp_name'];
		   
	
		$nombre_archivo= generarNombre($archivo);

		if(!is_dir($directoryName)){
			mkdir($file_path, 0755);
		}
		
		move_uploaded_file($temp_archivo, $file_path . '/' . $nombre_archivo);
		$oItem->Archivo = $nombre_archivo;
	
	
		$oItem->save();
		unset($oItem);
	
		GuardarActividadRadicado($modelo['Id_Radicado'], $modelo['Id_Funcionario'], $modelo['Codigo']);
	
		$http_response->SetRespuesta(0, 'Registro Exitoso', 'Se ha(n) agregado(s) exitosamente los datos de la radicacion!');
		$response = $http_response->GetRespuesta();
	
		echo json_encode($response);

	} catch (\Throwable $th) {
		$http_response->SetRespuesta(1, 'error', $th->getMessage());
		$response = $http_response->GetRespuesta();
	
		echo json_encode($response);
		//throw $th;

	}
	


	function GuardarActividadRadicado($idRadicado, $idFuncionario, $codigo){
		
		$oItem= new complex("Actividad_Radicado","Id_Actividad_Radicado");
	    $oItem->Id_Funcionario = $idFuncionario;
	    $oItem->Id_Radicado = $idRadicado;
	    $oItem->Detalle = "Se edito la radicacion con codigo ".$codigo;
	    $oItem->Estado = 'Edicion';
	    $oItem->save();
	    unset($oItem);
	}

	function validarArchivo(){
		global $archivo ,$extensions;
		$archivoExtension = getExtension($archivo);
		
		$valido = in_array($archivoExtension, $extensions);
	   
		if ( !$valido ) {
			throw new Exception("Error, El tipo de archivo no es permitido");
		}
	}
	function generarNombre($archvio){

		$permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyz';
		$name = substr(str_shuffle($permitted_chars), 0, 30);
		$archvioExtension = getExtension($archvio);
		$name.= '.'.$archvioExtension;
		
		return $name;
	}
	
	function getExtension($archivo){
		$archivoExtension = pathinfo($archivo['name'],PATHINFO_EXTENSION);
		return $archivoExtension;
	}
?>