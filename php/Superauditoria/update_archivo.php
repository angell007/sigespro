<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.complex.php');
	include_once('../../class/class.configuracion.php');
	include_once('../../class/class.http_response.php');
	include_once('../../class/class.consulta.php');
	require('../../class/class.guardar_archivos.php');

	//Objeto de la clase que almacena los archivos    
	$storer = new FileStorer();

	$http_response = new HttpResponse();
	$response = array();

	$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '');
	$soportes = ( isset( $_REQUEST['soportes'] ) ? $_REQUEST['soportes'] : '');

	$modelo = json_decode($modelo, true);
	$soportes = json_decode($soportes, true);

		
	if (!empty($_FILES['Archivo']['name'])){
	    //GUARDAR ARCHIVO Y RETORNAR NOMBRE DEL MISMO
	    $nombre_archivo = $storer->UploadFileToRemoteServer($_FILES, 'store_remote_files', 'IMAGENES/AUDITORIAS/'.$modelo['Id_Auditoria'].'/');
	    $_filename1 = $nombre_archivo[0];
	    
		// $posicion1 = strrpos($_FILES['Archivo']['name'],'.')+1;
		// $extension1 =  substr($_FILES['Archivo']['name'],$posicion1);
		// $extension1 =  strtolower($extension1);
		// $_filename1 = uniqid() . "." . $extension1;
		// $_file1 = $MY_FILE . "IMAGENES/AUDITORIAS/".$modelo['Id_Auditoria']."/" . $_filename1;
		
		// $subido1 = move_uploaded_file($_FILES['Archivo']['tmp_name'], $_file1);
		// 	if ($subido1){		
		// 		@chmod ( $_file1, 0777 );
		// 		$nombre_archivo = $_filename1;
		// 	} 
	}
	
	$oItem= new complex("Auditoria", "Id_Auditoria", $modelo['Id_Auditoria']);
    $oItem->Estado="Con Observacion";    
    $oItem->Archivo=$_filename1;    
    $oItem->save();
	unset($oItem);
	
	foreach ($soportes as $value) {
		$oItem= new complex("Soporte_Auditoria", "Id_Soporte_Auditoria", $value['Id_Soporte_Auditoria']);
		$oItem->Paginas=$value['Paginas'];
		$oItem->save();
		unset($oItem);

	}

	GuardarActividadAuditoria($modelo['Id_Auditoria'], $modelo['Identificacion_Funcionario']);
	EliminarAlerta($modelo['Id_Auditoria']);
	

    $http_response->SetRespuesta(0, 'Registro Exitoso', 'Se ha actualizado correctamente los datos de la Auditoria!');
    $response = $http_response->GetRespuesta();

	echo json_encode($response);

	function GuardarActividadAuditoria($idAuditoria, $idFuncionario){
		
		$oItem= new complex("Actividad_Auditoria","Id_Actividad_Auditoria");
	    $oItem->Identificacion_Funcionario = $idFuncionario;
	    $oItem->Id_Auditoria = $idAuditoria;
	    $oItem->Detalle = "Se actualiza el documento de la auditoria" ;
        $oItem->Estado ='Actualizacion' ;
		$oItem->Fecha=date("Y-m-d H:i:s");
		$oItem->Observacion= "Sin Observacion";
	    $oItem->save();
	    unset($oItem);
	}
	function EliminarAlerta($id){
		$query='SELECT Id_Alerta FROM Alerta WHERE Tipo="Auditoria" AND Id='.$id.'  ORDER BY Id_Alerta DESC LIMIT 1';
		$oCon= new consulta();
		$oCon->setQuery($query);		
		$id_alerta = $oCon->getData();
		unset($oCon);

		$oItem= new complex('Alerta','Id_Alerta',$id_alerta['Id_Alerta']);
		$oItem->delete();
		unset($oItem);
	}

?>