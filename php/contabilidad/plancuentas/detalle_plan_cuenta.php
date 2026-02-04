<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

    require_once('../../../config/start.inc.php');
    include_once('../../../class/class.lista.php');
    include_once('../../../class/class.complex.php');
    include_once('../../../class/class.consulta.php');
    include_once('../../../class/class.http_response.php');

	$id_cuenta = (isset($_REQUEST['id_cuenta']) && $_REQUEST['id_cuenta'] != "") ? $_REQUEST['id_cuenta'] : '';

	$respuesta = array();
    $http_response = new HttpResponse();

    if($id_cuenta == ''){
        $http_response->SetRespuesta(1,'Detalle','No se envio el plan de cuenta para proceder, contacte con el administrador!');
    	$respuesta = $http_response->GetRespuesta();
    	echo json_encode($respuesta);
    	return;
    }

    $query = '
        SELECT
        Plan_Cuentas.*, (SELECT Nombre FROM Banco WHERE Id_Banco = Plan_Cuentas.Cod_Banco) AS Nombre_Banco
        FROM Plan_Cuentas
        WHERE
            Id_Plan_Cuentas = '.$id_cuenta;

    $oCon= new consulta();
    $oCon->setQuery($query);
    $plan_cuenta = $oCon->getData();
    unset($oCon);

    $http_response->SetDatosRespuesta($plan_cuenta);
	$http_response->SetRespuesta(0, 'Detalle','Operación Exitosa!');
	$respuesta = $http_response->GetRespuesta();
	echo json_encode($respuesta);
?>