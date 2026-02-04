<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	include_once('../../../class/class.querybasedatos.php');
	include_once('../../../class/class.http_response.php');

	$queryObj = new QueryBaseDatos();
	$response = array();
	$http_response = new HttpResponse();

	$id_parcial = ( isset( $_REQUEST['id_parcial'] ) ? $_REQUEST['id_parcial'] : '' );
	$nuevo_descuento = ( isset( $_REQUEST['nuevo_descuento'] ) ? $_REQUEST['nuevo_descuento'] : '' );

	// var_dump($id_parcial);
	// var_dump($nuevo_descuento);
	// exit;

	ActualizarDescuento($id_parcial, $nuevo_descuento);

	$http_response->SetRespuesta(0, 'Registro Exitoso', 'Se ha actualizado el descuento del parcial exitosamente!');
	$response = $http_response->GetRespuesta();

	echo json_encode($response);

	function ActualizarTasa($id_parcial, $nuevo_descuento){
		global $queryObj;

		$query = 'UPDATE Nacionalizacion_Parcial SET Descuento_Parcial = '.number_format($nuevo_descuento, 2, ".", "").' WHERE Id_Nacionalizacion_Parcial = '.$id_parcial;
		$queryObj->SetQuery($query);
		$queryObj->QueryUpdate();
	}
?>