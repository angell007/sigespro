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

	$http_response = new HttpResponse();
	$response = array();

	$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '');
	$func = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '');

	$datos = json_decode($datos, true);

	$oItem = new complex('Dispensacion','Id_Dispensacion',$datos['Id_Dispensacion']);
	$oItem->Estado_Dispensacion = "Anulada";
	$oItem->save();
	unset($oItem);

	$oItem = new complex('Auditoria','Id_Auditoria',$datos['Id_Auditoria']);
	$oItem->Estado = "Anulada";
	$oItem->save();
	unset($oItem);

	$query = "SELECT Id_Inventario, Cantidad_Entregada FROM Producto_Dispensacion WHERE Id_Dispensacion=$datos[Id_Dispensacion] AND Lote <> 'Pendiente'";
	$oCon= new consulta();
	$oCon->setQuery($query);
	$oCon->setTipo('Multiple');
	$productos = $oCon->getData();
	unset($oCon);

	foreach ($productos as $prod) { // Ingresar nuevamente las cantidades al inventario.
		$oItem = new complex('Inventario','Id_Inventario',$prod['Id_Inventario']);
		$cantidad = number_format($prod['Cantidad_Entregada'],0,"","");
		$cantidad_final = $oItem->Cantidad + $cantidad;
		$oItem->Cantidad = number_format($cantidad_final,0,"","");
		$oItem->save();
		unset($oItem);
	}
	$ActividadDis["Identificacion_Funcionario"]=$func;
	$ActividadDis["Id_Dispensacion"] = $datos['Id_Dispensacion'];
	$ActividadDis['Fecha'] = date("Y-m-d H:i:s");
	$ActividadDis["Detalle"] = "Esta dispensacion fue anulada por el siguiente motivo: ". $datos['Motivo_Anulacion'];
	$ActividadDis["Estado"] = "Anulada";

	$oItem = new complex("Actividades_Dispensacion","Id_Actividades_Dispensacion");
	foreach($ActividadDis as $index=>$value) {
		$oItem->$index=$value;
	}
	$oItem->save();
	unset($oItem);

	$ActividadAud["Identificacion_Funcionario"]=$func;
	$ActividadAud["Id_Auditoria"] = $datos['Id_Auditoria'];
	$ActividadAud['Fecha'] = date("Y-m-d H:i:s");
	$ActividadAud["Detalle"] = "Esta auditoria  fue anulada por el siguiente motivo: ". $datos['Motivo_Anulacion'];
	$ActividadAud["Estado"] = "Anulada";

	$oItem = new complex("Actividad_Auditoria","Id_Actividad_Auditoria");
	foreach($ActividadAud as $index=>$value) {
		$oItem->$index=$value;
	}
	$oItem->save();
	unset($oItem);

    $http_response->SetRespuesta(0, 'Registro Exitoso', 'Se ha anulado correctamente la auditoria y la dispensacion!');
    $response = $http_response->GetRespuesta();

	echo json_encode($response);



?>