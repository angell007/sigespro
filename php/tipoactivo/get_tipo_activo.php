<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	date_default_timezone_set('America/Bogota');

	require_once('../../config/start.inc.php');
	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.paginacion.php');
	include_once('../../class/class.http_response.php');

	$http_response = new HttpResponse();

	$id_tipo_activo = ( isset( $_REQUEST['id_tipo_activo'] ) ? $_REQUEST['id_tipo_activo'] : '' );

	$query = '
		SELECT 
			*
		FROM Tipo_Activo_Fijo
		WHERE
			Id_Tipo_Activo_Fijo ='.$id_tipo_activo;

	//Se crea la instancia que contiene la consulta a realizar
    $queryObj = new QueryBaseDatos($query);

    //Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
    $response = $queryObj->Consultar('simple');
    GetObjectosCuenta($queryObj);

	unset($queryObj);

	echo json_encode($response);

	function GetObjectosCuenta($queryObj){
		global $response;

		$query_cuenta_depreciacion_niif = '
			SELECT 
				Id_Plan_Cuentas,
				Codigo_Niif,
				CONCAT_WS(" ",Codigo_Niif,"-",Nombre_Niif) as Nombre_Cuenta_Niif
			FROM Plan_Cuentas
			WHERE  
				Id_Plan_Cuentas = '.$response['query_result']['Id_Plan_Cuenta_Depreciacion_NIIF']; 
		
		$query_cuenta_depreciacion_pcga = '
			SELECT 
				Id_Plan_Cuentas,
				Codigo,
				CONCAT_WS(" ",Codigo,"-",Nombre) as Nombre_Cuenta
			FROM Plan_Cuentas
			WHERE  
				Id_Plan_Cuentas = '.$response['query_result']['Id_Plan_Cuenta_Depreciacion_PCGA']; 

		$query_cuenta_niif = '
			SELECT 
				Id_Plan_Cuentas,
				Codigo_Niif,
				CONCAT_WS(" ",Codigo_Niif,"-",Nombre_Niif) as Nombre_Cuenta_Niif
			FROM Plan_Cuentas
			WHERE  
				Id_Plan_Cuentas = '.$response['query_result']['Id_Plan_Cuenta_NIIF']; 

		$query_cuenta_pcga = '
			SELECT 
				Id_Plan_Cuentas,
				Codigo,
				CONCAT_WS(" ",Codigo,"-",Nombre) as Nombre_Cuenta
			FROM Plan_Cuentas
			WHERE  
				Id_Plan_Cuentas = '.$response['query_result']['Id_Plan_Cuenta_PCGA']; 
		$query_credito_pcga = '
			SELECT 
				Id_Plan_Cuentas,
				Codigo,
				CONCAT_WS(" ",Codigo,"-",Nombre) as Nombre_Cuenta
			FROM Plan_Cuentas
			WHERE  
				Id_Plan_Cuentas = '.$response['query_result']['Id_Plan_Cuenta_Credito_Depreciacion_PCGA']; 
		$query_credito_niff = '
			SELECT 
				Id_Plan_Cuentas,
				Codigo_Niif,
				CONCAT_WS(" ",Codigo_Niif,"-",Nombre_Niif) as Nombre_Cuenta_Niif
			FROM Plan_Cuentas
			WHERE  
				Id_Plan_Cuentas = '.$response['query_result']['Id_Plan_Cuenta_Credito_Depreciacion_NIIF']; 

		$queryObj->setQuery($query_cuenta_depreciacion_niif); 
		$cuenta_depreciacion_niif = $queryObj->ExecuteQuery('simple');
		
		$queryObj->setQuery($query_cuenta_depreciacion_pcga); 
		$cuenta_depreciacion_pcga = $queryObj->ExecuteQuery('simple');

		$queryObj->setQuery($query_cuenta_niif); 
		$cuenta_niif = $queryObj->ExecuteQuery('simple');

		$queryObj->setQuery($query_cuenta_pcga); 
		$cuenta_pcga = $queryObj->ExecuteQuery('simple');

		$queryObj->setQuery($query_credito_pcga); 
		$credito_pcga = $queryObj->ExecuteQuery('simple');

		$queryObj->setQuery($query_credito_niff); 
		$credito_niif = $queryObj->ExecuteQuery('simple');

		$response['query_result']['cuenta_depreciacion_niif'] = $cuenta_depreciacion_niif;
		$response['query_result']['cuenta_depreciacion_pcga'] = $cuenta_depreciacion_pcga;
		$response['query_result']['cuenta_niif'] = $cuenta_niif;
		$response['query_result']['cuenta_pcga'] = $cuenta_pcga;
		$response['query_result']['cuenta_depreciacion_credito_pcga'] = $credito_pcga;
		$response['query_result']['cuenta_depreciacion_credito_niif'] = $credito_niif;
	}
?>