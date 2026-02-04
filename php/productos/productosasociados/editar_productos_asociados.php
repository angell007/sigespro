<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../../config/start.inc.php');
	include_once('../../../class/class.consulta.php');
	include_once('../../../class/class.configuracion.php');
	include_once('../../../class/class.http_response.php');

	$http_response = new HttpResponse();
	$response = array();
	
	$id_genericos = ( isset( $_REQUEST['id_genericos'] ) ? $_REQUEST['id_genericos'] : '' );
	$id_asociado = ( isset( $_REQUEST['id_asociado'] ) ? $_REQUEST['id_asociado'] : '' );
	$productos_asociados = ( isset( $_REQUEST['productos_asociados'] ) ? $_REQUEST['productos_asociados'] : '' );
	$productos_asociados = json_decode($productos_asociados, true);

	if (count($productos_asociados) == 0) {
		
		$http_response->SetRespuesta(2, 'Alerta', 'No hay productos para realizar el registro, verifique o contacte al administrador del sistema!');
		$response = $http_response->GetRespuesta();
		echo json_encode($response);
		return;
	}else{
			
		$oItem= new consulta();
		$ids = ConcatenarIdProductos($productos_asociados);
		$ids2 = ConcatenarIdProductos2($productos_asociados);
		$id_genericos = $id_genericos? "'$id_genericos'" : json_encode(null);
		$query = "UPDATE Producto_Asociado 
				set Producto_Asociado = '$ids',
				Asociados2 = '$ids2',
				Id_Asociado_Genericos = $id_genericos
				where Id_Producto_Asociado = $id_asociado";
		$oItem->setQuery($query);
		$oItem->getData();
		unset($oItem);

	    $http_response->SetRespuesta(0, 'Actualizacion Exitosa', 'Se ha actualizado la asociacion exitosamente!');
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