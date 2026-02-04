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
	
	$id_controlado = ( isset( $_REQUEST['id_controlado'] ) ? $_REQUEST['id_controlado'] : '' );
	$producto_controlado = ( isset( $_REQUEST['producto_controlado'] ) ? $_REQUEST['producto_controlado'] : '' );
	$producto_controlado = json_decode($producto_controlado, true);

	$fecha = date('Y-m-d H:i:s');

	$oItem= new complex("Producto_Control_Cantidad","Id_Producto_Control_Cantidad", $id_controlado);
	$oItem->Id_Producto = $producto_controlado['Id_Producto'];
	$oItem->Cantidad_Minima = $producto_controlado['Cantidad_Minima'];
	$oItem->Cantidad_Maxima = $producto_controlado['Cantidad_Maxima'];
	$oItem->Cantidad_Presentacion = $producto_controlado['Cantidad_Presentacion'];
	$oItem->Multiplo = $producto_controlado['Multiplo'];
	$oItem->Ultima_Edicion = $fecha;
	$oItem->save();

    $http_response->SetRespuesta(0, 'Actualizacion Exitosa', 'Se han actualizado las cantidades exitosamente!');
    $response = $http_response->GetRespuesta();
	
	echo json_encode($response);

	function ConcatenarIdProductos($productos){

		$ids = '';

		foreach ($productos as $p) {
			$ids .= $p['Id_Producto'].", ";
		}

		$ids = trim($ids, ", ");
		return $ids;
	}
?>