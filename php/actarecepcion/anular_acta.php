<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');
	date_default_timezone_set('America/Bogota');
	require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.http_response.php');
    include_once('../../class/class.contabilizar.php');
	require_once('../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */

	$queryObj = new QueryBaseDatos();
	$response = array();
	$http_response = new HttpResponse();
    $contabilizar = new Contabilizar();

    $modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );
    $modelo = json_decode($modelo, true);

    
    $oItem = new complex("Acta_Recepcion","Id_Acta_Recepcion", $modelo['Id_Acta_Recepcion']);
    $data = $oItem->getData();
    $fecha = date('Y-m-d',strtotime($data['Fecha_Creacion']));
    if ($contabilizar->validarMesOrAnioCerrado($fecha)) {
        $oItem->Estado="Anulada";
        $oItem->Id_Causal_Anulacion=$modelo['Id_Causal_Anulacion'];
        $oItem->Observaciones_Anulacion=$modelo['Observaciones'];
        $oItem->Funcionario_Anula=$modelo['Identificacion_Funcionario'];
        $oItem->Fecha_Anulacion=date("Y-m-d H:i:s");
        $oItem->save();
        unset($oItem);
    
    
      
    
        $query = 'SELECT *
        FROM  Actividad_Orden_Compra 
        WHERE
            Detalles LIKE "Se recibio el acta%" AND  Id_Acta_Recepcion_Compra = '.$modelo['Id_Acta_Recepcion'];
    
        $queryObj->SetQuery($query);
        $actividad = $queryObj->ExecuteQuery('simple');
    
       
    
    
    
        $oItem = new complex("Actividad_Orden_Compra","Id_Actividad_Orden_Compra", $actividad['Id_Actividad_Orden_Compra']);
        $oItem->delete();
        unset($oItem);
    
    
        $oItem = new complex("Orden_Compra_Nacional","Id_Orden_Compra_Nacional", $actividad['Id_Orden_Compra_Nacional']);
        $oItem->Estado="Pendiente";
        $oItem->save();
        unset($oItem);
    
        AnularMovimientoContable($modelo['Id_Acta_Recepcion']);
    
        $http_response->SetRespuesta(0, 'Registro Exitoso', 'Se ha anulo correctamente el acta de recepcion!');
        $response = $http_response->GetRespuesta();
    } else {
        $http_response->SetRespuesta(3, 'No es posible', 'No es posible anular esta acta debido a que el mes o el año del documento ha sido cerrado contablemente. Si tienes alguna duda por favor comunicarse al Dpto. Contabilidad.');
        $response = $http_response->GetRespuesta();
    }
    
	echo json_encode($response);

    function AnularMovimientoContable($idRegistroModulo){
        global $contabilizar;

        $contabilizar->AnularMovimientoContable($idRegistroModulo, 15);
    }
?>