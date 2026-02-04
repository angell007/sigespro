<?php
	header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    require_once('../../config/start.inc.php');
    include_once('../../class/class.lista.php');
    include_once('../../class/class.complex.php');
    include_once('../../class/class.consulta.php');
    include('../../class/class.system_constants.php');
    include('../../class/class.http_response.php');

    $respuesta = array();
    $http_response = new HttpResponse();

    $id_centro = ( isset( $_REQUEST['id_centro'] ) ? $_REQUEST['id_centro'] : '' );
    $id_centro = json_decode($id_centro);
    
    
    if(isset($datos['Id_Centro_Costo']) && $datos['Id_Centro_Costo'] != ''){
        $http_response->SetCodigoYMensajeRespuesta(1, 'No se envío un centro de costo para proceder, contacte con el administrador!');
    	$respuesta = $http_response->GetResponse();
    	echo json_encode($respuesta);
    	return;
    }
    
    $oItem = new complex("Centro_Costo","Id_Centro_Costo", $id_centro);
    
    if($oItem->Estado == 'Activo'){
       $oItem->Estado = 'Inactivo';
    }else{
        $oItem->Estado = 'Activo';
    }

	$oItem->save();
	unset($oItem);

	$http_response->SetCodigoYMensajeRespuesta(0, 'Operación Exitosa!');
	$respuesta = $http_response->GetResponse();
	echo json_encode($respuesta);

?>