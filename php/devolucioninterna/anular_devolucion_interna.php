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

	$id_devolucion_interna = ( isset( $_REQUEST['id_devolucion_interna'] ) ? $_REQUEST['id_devolucion_interna'] : '' );
	$id_funcionario = ( isset( $_REQUEST['id_funcionario'] ) ? $_REQUEST['id_funcionario'] : '' );

	

	ActualizarEstadoActa($id_devolucion_interna);
	GuardarActividadActa($id_devolucion_interna,  $id_funcionario);

	$http_response->SetRespuesta(0, 'Anulacion Exitosa', 'Se ha anulado el acta de recepcion exitosamente!');
	$response = $http_response->GetRespuesta();

	unset($queryObj);
	unset($http_response);

	echo json_encode($response);

	function ActualizarEstadoActa($id_devolucion_interna){
		global $queryObj;

        $query = 'UPDATE Devolucion_Interna SET Estado = "Anulada" WHERE Id_Devolucion_Interna = '.$id_devolucion_interna;
        
      
		$queryObj->SetQuery($query);
		$queryObj->QueryUpdate();
	}

	function GuardarActividadActa($id_devolucion_interna, $id_funcionario){
		
		$oItem = new complex("Actividad_Devolucion_Interna","Id_Actividad_Devolucion_Interna");

		$oItem->Identificacion_Funcionario = $id_funcionario;
		$oItem->Id_Devolucion_Interna = $id_devolucion_interna;
		$oItem->Detalles = "Se ha anula la devolucion interna ";
        $oItem->Fecha=date("Y-m-d H:i:s");
        $oItem->save();
        unset($oItem);
	}

	
?>