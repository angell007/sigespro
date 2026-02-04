<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.http_response.php');

	$http_response = new HttpResponse();

	$id_retencion = ( isset( $_REQUEST['id_retencion'] ) ? $_REQUEST['id_retencion'] : '' );

	$query = '
		SELECT 
            *
		FROM Retencion
        WHERE
            Id_Retencion = '.$id_retencion;

    $queryObj = new QueryBaseDatos($query);
    $fondo_pension = $queryObj->Consultar('simple');
    $fondo_pension['obj_plan_cuenta'] = GetObjetoCuentaContable($fondo_pension['query_result']['Id_Plan_Cuenta']);

	echo json_encode($fondo_pension);

	function GetObjetoCuentaContable($idCuentaContable){
		global $queryObj;

		$query = '
			SELECT 
	            Centro_Costo,
	            CONCAT_WS(" - ",Nombre,Codigo) AS Codigo,
	            Id_Plan_Cuentas
			FROM Plan_Cuentas
	        WHERE
	            Id_Plan_Cuentas = '.$idCuentaContable;

        $queryObj->SetQuery($query);
        $obj_plan_cuenta = $queryObj->ExecuteQuery('simple');

        return $obj_plan_cuenta;
	}
?>