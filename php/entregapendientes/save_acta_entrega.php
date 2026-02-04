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
	
	require '../../class/class.awsS3.php';
	//Objeto de la clase que almacena los archivos    

	$http_response = new HttpResponse();
	$response = array();

	$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '');
    
	$modelo = json_decode($modelo, true);
	$oItem = new complex("Auditoria", "Id_Dispensacion", $modelo['Id_Dispensacion']);
	$auditoria= $oItem->getData();
	
	$s3 = new AwsS3();
	$ruta = 'dispensacion/auditoria/soportes/' . $auditoria['Id_Auditoria'] . '/' . 'Acta_Entrega';

	if (!empty($_FILES['acta']['name'])){ // Archivo de la Acta de Entrega.
		//GUARDAR ARCHIVO Y RETORNAR NOMBRE DEL MISMO
		$uri ="";
		try {
			$uri = $s3->putObject( $ruta, $_FILES['acta']);
			//code...
		} catch (\Throwable $th) {
			//throw $th;
		}
		$nombre_archivo = $uri;

		if ($nombre_archivo != '') {
			$oItem = new complex('Dispensacion','Id_Dispensacion',$modelo["Id_Dispensacion"]);
			$dis = $oItem->getData();
			$oItem->Acta_Entrega = $nombre_archivo;
			$oItem->save();
			unset($oItem);
			
			$query="UPDATE Alerta SET Respuesta='Si' WHERE Detalles LIKE '%Se ha eliminado el acta de entrega de la dispensacion%' AND Modulo LIKE '".$dis["Codigo"]."'";
            $oCon = new consulta();
            $oCon->setQuery($query);
            $oCon->createData();
            unset($oCon);
			
			$http_response->SetRespuesta(0, 'Registro Exitoso', 'Se ha guardado el acta de entrega correctamente!');
            $response = $http_response->GetRespuesta();
		}else{
		    $http_response->SetRespuesta(1, 'Registro Erroneo', 'El Archivo no se ha podido guardar, por favor verifique!');
            $response = $http_response->GetRespuesta();
		}
        
	}else{
	    $http_response->SetRespuesta(1, 'Registro Erroneo', 'Hay un Errorr con el Archivo, por favor verifique!');
        $response = $http_response->GetRespuesta();
	}

    

	echo json_encode($response);


	

	
?>