<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.contabilizar.php');
	include_once('../../class/class.http_response.php');
	require_once('../../class/class.configuracion.php');
	require_once('../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */

	$queryObj = new QueryBaseDatos();
	$http_response = new HttpResponse();
	$response = array();

	$id_acta = ( isset( $_REQUEST['id_acta'] ) ? $_REQUEST['id_acta'] : '' );
	$id_orden = ( isset( $_REQUEST['id_orden'] ) ? $_REQUEST['id_orden'] : '' );
	$codigo_acta = ( isset( $_REQUEST['codigo_acta'] ) ? $_REQUEST['codigo_acta'] : '' );
	$codigo_orden = ( isset( $_REQUEST['codigo_orden'] ) ? $_REQUEST['codigo_orden'] : '' );
	$id_funcionario = ( isset( $_REQUEST['id_funcionario'] ) ? $_REQUEST['id_funcionario'] : '' );

	// var_dump($modelo);
	// var_dump($productos);
	// var_dump($facturas);
	 //var_dump($_FILES);
	// exit;

	ActualizarEstadoActa($id_acta);
	GuardarActividadActa($codigo_acta, $codigo_orden, $id_funcionario, $id_orden);

	$http_response->SetRespuesta(0, 'Anulacion Exitosa', 'Se ha anulado el acta de recepcion exitosamente!');
	$response = $http_response->GetRespuesta();

	unset($queryObj);
	unset($http_response);

	echo json_encode($response);

	function ActualizarEstadoActa($id_acta){
		global $queryObj;

		$query = 'UPDATE Acta_Recepcion_Internacional SET Estado = "Anulada" WHERE Id_Acta_Recepcion_Internacional = '.$id_acta;
		$queryObj->SetQuery($query);
		$queryObj->QueryUpdate();
	}

	function GuardarActividadActa($codigo_acta, $codigo_orden, $id_funcionario, $id_orden){
		
		$oItem = new complex("Actividad_Orden_Compra_Internacional","Id_Actividad_Orden_Compra_Internacional");

		$oItem->Identificacion_Funcionario = $id_funcionario;
		$oItem->Id_Orden_Compra_Internacional = $id_orden;
		$oItem->Accion = "Anular";
		$oItem->Descripcion = "Se ha anulado el acta con codigo ".$codigo_acta;

	    $oItem->save();
	    unset($oItem);
	}

	function GetParcialesActa(){

	}
?>