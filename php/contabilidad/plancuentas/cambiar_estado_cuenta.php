<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.lista.php');
	include_once('../../class/class.complex.php');
	include_once('../../class/class.consulta.php');

	$respuesta = array();
    $http_response = new HttpResponse();

    $id_cuenta = ( isset( $_REQUEST['id_cuenta'] ) ? $_REQUEST['id_cuenta'] : '' );
    $id_cuenta = json_decode($id_cuenta);
    
    
    if($id_cuenta == ''){
        $http_response->SetCodigoYMensajeRespuesta(1, 'No se envio un plan de cuenta para proceder, contacte con el administrador!');
    	$respuesta = $http_response->GetResponse();
    	echo json_encode($respuesta);
    	return;
    }
    
    $oItem = new complex("Plan_Cuentas","Id_Plan_Cuentas", $id_cuenta);
    
    if($oItem->Estado == 'ACTIVO'){
       $oItem->Estado = 'INACTIVO';
    }else{
        $oItem->Estado = 'ACTIVO';
    }

	$oItem->save();
	unset($oItem);

	$http_response->SetCodigoYMensajeRespuesta(0, 'Operaci贸n Exitosa!');
	$respuesta = $http_response->GetResponse();
	echo json_encode($respuesta);
?>