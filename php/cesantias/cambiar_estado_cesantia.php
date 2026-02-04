<?php
	header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    require_once('../../config/start.inc.php');
    include_once('../../class/class.querybasedatos.php');
    include('../../class/class.http_response.php');

    $respuesta = array();
    $queryObj = new QueryBaseDatos();
    $http_response = new HttpResponse();

    $id_cesantia = ( isset( $_REQUEST['id_cesantia'] ) ? $_REQUEST['id_cesantia'] : '' );
    $estado = ( isset( $_REQUEST['estado'] ) ? $_REQUEST['estado'] : '' );
    $observacion = ( isset( $_REQUEST['observacion'] ) ? $_REQUEST['observacion'] : '' );
    $observacion = utf8_decode($observacion);

    $obser = '';
    if( $estado == 'Rechazada'){
        $obser = 'Observacion_Rechazada';
    }else{
        $obser = 'Observacion_Aprobacion';
    }
    if($id_cesantia == ''){
        $http_response->SetRespuesta(1, 'Error', 'Ocurrio un error con el identificador de la cesantia, contacte con el administrador!');
    	$respuesta = $http_response->GetRespuesta();
    }else{
        $query_update = "UPDATE Cesantia 
                         SET Estado = '$estado', $obser = '$observacion'
                         WHERE Id_Cesantia = $id_cesantia";
        $queryObj->SetQuery($query_update);
        $queryObj->QueryUpdate();
        $titulo = $estado = 'Aprobada' ? 'Aprobacion exitosa!' : 'Rechazo exitoso!';
        $mensaje = $estado = 'Aprobada' ? 'Cesantia aprobada exitosamente!' : 'Cesantia rechazada exitosamente!';
        
        $http_response->SetRespuesta(0, $titulo, $mensaje);
        $respuesta = $http_response->GetRespuesta();
    }    
    
	echo json_encode($respuesta);
?>