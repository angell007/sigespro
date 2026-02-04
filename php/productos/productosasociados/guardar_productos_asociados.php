<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../../config/start.inc.php');
	include_once('../../../class/class.complex.php');
	include_once('../../../class/class.configuracion.php');
	include_once('../../../class/class.http_response.php');

	$http_response = new HttpResponse();
	$response = array();

	$id_genericos = ( isset( $_REQUEST['id_genericos'] ) ? $_REQUEST['id_genericos'] : '' );
	$productos_asociados = ( isset( $_REQUEST['productos_asociados'] ) ? $_REQUEST['productos_asociados'] : '' );
	$productos_asociados = json_decode($productos_asociados, true);
	$fecha = date('Y-m-d H:i:s');

	if (count($productos_asociados) == 0) {
		
		$http_response->SetRespuesta(2, 'Alerta', 'No hay productos para realizar el registro, verifique o contacte al administrador del sistema!');
		$response = $http_response->GetRespuesta();
		echo json_encode($response);
		return;
	}else{
			
		$oItem= new complex("Producto_Asociado","Id_Producto_Asociado");
		$ids = ConcatenarIdProductos($productos_asociados);
		$ids2 = ConcatenarIdProductos2($productos_asociados);
		$oItem->Producto_Asociado = $ids;
		$oItem->Asociados2 = $ids2;
		$id_genericos? $oItem->Id_Asociado_Genericos= "$id_genericos": '';
		$oItem->save();
		unset($oItem);

	    $http_response->SetRespuesta(0, 'Registro Exitoso', 'Se ha registrado la asociacion exitosamente!');
	    $response = $http_response->GetRespuesta();
	}

	echo json_encode($response);

	function ConcatenarIdProductos($productos){

		$ids = '';

		$ids= implode(', ',array_map( function($p){ return $p['Id_Producto'];},$productos));
		return $ids;
	}
	function ConcatenarIdProductos2($productos){

		$ids = '';

		$ids="-". implode('-',array_map( function($p){ return $p['Id_Producto'];},$productos))."-";
		return $ids;
	}
?>