<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.http_response.php');

	$http_response = new HttpResponse();

	$id_orden = ( isset( $_REQUEST['id_orden'] ) ? $_REQUEST['id_orden'] : '' );

	$query = '
		SELECT 
			*
		FROM Orden_Compra_Internacional
		WHERE
			Id_Orden_Compra_Internacional ='.$id_orden;

    $queryObj = new QueryBaseDatos($query);
    $response = $queryObj->Consultar('simple');
    $otros_gastos = GetOtrosGastosOrden($id_orden);
    $response['otros_gastos'] = $otros_gastos;

	unset($queryObj);

	echo json_encode($response);

	function GetOtrosGastosOrden($id_orden){
		global $queryObj;

		$query = '
			SELECT 
				*
			FROM Orden_Compra_Internacional_Otro_Gasto
			WHERE
				Id_Orden_Compra_Internacional ='.$id_orden;

		$queryObj->SetQuery($query);
		$otros_gastos = $queryObj->ExecuteQuery('multiple');

		return $otros_gastos;
	}
?>