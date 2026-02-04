<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	//require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.http_response.php');
	require_once('../../class/class.configuracion.php');
require_once('../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */

	$configuracion = new Configuracion();

	$queryObj = new QueryBaseDatos();
	$response = array();
	$http_response = new HttpResponse();

	$modelo = (isset($_REQUEST['modelo']) && $_REQUEST['modelo'] != '') ? $_REQUEST['modelo'] : '';

	$modelo = (array) json_decode($modelo, true);
	$productos = $modelo['ProductosCompraInternacional'];

	unset($modelo['ProductosCompraInternacional']);	
	unset($modelo['Id_Orden_Compra_Internacional']);
	unset($modelo['Codigo_Qr']);

	// var_dump($modelo);
	// var_dump($productos);
	// exit;

	$cod = $configuracion->getConsecutivo('Orden_Compra_Internacional','Orden_Compra_Internacional');
    $modelo['Codigo']= $cod;

	$oItem = new complex("Orden_Compra_Internacional","Id_Orden_Compra_Internacional");

	foreach($modelo as $index=>$value) {
        $oItem->$index=$value;
    }

    $oItem->save();
    $id_orden = $oItem->getId();
    unset($oItem);

    $qr = generarqr('ordencomprainternacional',$id_orden,'IMAGENES/QR/');
	$oItem = new complex("Orden_Compra_Internacional","Id_Orden_Compra_Internacional",$id_orden);
	$oItem->Codigo_Qr=$qr;
	$oItem->save();
	unset($oItem);

    foreach ($productos as $p) {
    	$oItem = new complex("Producto_Orden_Compra_Internacional","Id_Producto_Orden_Compra_Internacional");

    	$oItem->Id_Orden_Compra_Internacional = $id_orden;
    	$oItem->Id_Producto = $p['Id_Producto'];
    	$oItem->Costo = number_format($p['Costo'],4,".","");
    	$oItem->Cantidad = $p['Cantidad'];
    	$oItem->Cantidad_Caja = $p['Cantidad_Caja'];
    	$oItem->Empaque = $p['Empaque'];
    	$oItem->Subtotal = number_format($p['Subtotal'],2,".","");
    	$oItem->Caja_Ancho = number_format($p['Caja_Ancho'],2,".","");
    	$oItem->Caja_Alto = number_format($p['Caja_Alto'],2,".","");
    	$oItem->Caja_Largo = number_format($p['Caja_Largo'],2,".","");
    	$oItem->Caja_Volumen = number_format($p['Caja_Volumen'],6,".","");
    	
    	$oItem->save();
	    unset($oItem);
    }

	$http_response->SetRespuesta(0, 'Registro Exitoso', 'Se ha guardado la orden exitosamente!');
	$response = $http_response->GetRespuesta();

	echo json_encode($response);
?>