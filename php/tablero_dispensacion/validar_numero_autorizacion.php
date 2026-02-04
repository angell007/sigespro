<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.http_response.php');
	include_once('../../class/class.utility.php');

	$http_response = new HttpResponse();
	$queryObj = new QueryBaseDatos();
	$util = new Utility();

	$numero= ( isset( $_REQUEST['numero'] ) ? $_REQUEST['numero'] : '' );

	$query="SELECT 
		Id_Dispensacion
		FROM Positiva_Data WHERE numeroAutorizacion='$numero'";



	$queryObj->SetQuery($query);
	$data = $queryObj->Consultar("simple");
	if($data['query_result']['Id_Dispensacion']!=''){
		$res=true;
	}else{
		$res=false;
	}


	echo json_encode($res);
