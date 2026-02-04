<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	require_once('../../../config/start.inc.php');
	include_once('../../../class/class.querybasedatos.php');
	include_once('../../../class/class.http_response.php');
	require_once('../../../class/class.configuracion.php');
	require_once('../../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */

	$queryObj = new QueryBaseDatos();
	$response = array();
	$http_response = new HttpResponse();
	
    $id = ( isset( $_REQUEST['Id_Doc_Inventario_Fisico'] ) ? $_REQUEST['Id_Doc_Inventario_Fisico'] : '' );
    $productos = ( isset( $_REQUEST['Productos'] ) ? $_REQUEST['Productos'] : '' );
	
	

    $prod = (array) json_decode($productos, true);

    
    $oItem= new complex("Doc_Inventario_Fisico","Id_Doc_Inventario_Fisico",$id);
    $oItem->Lista_Productos = $productos;
    $oItem->save();
    unset($oItem);

	$http_response->SetRespuesta(0, 'Registro Exitoso', 'Producto Agregado de Manera Exitosa!');
	$response = $http_response->GetRespuesta();

	echo json_encode($response);

?>