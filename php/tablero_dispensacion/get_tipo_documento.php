<?php
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
	header('Content-Type: application/json');

	include_once('../../class/class.querybasedatos.php');
	include_once('../../class/class.http_response.php');
	include_once('../../class/class.utility.php');

	$http_response = new HttpResponse();
	$queryObj = new QueryBaseDatos();

		
	$query="SELECT * FROM Tipo_Documento WHERE Id_Tipo_Documento IN (1,5,8,11,15,16,17,19) " ;
	$queryObj->SetQuery($query);
	$tipo_documento = $queryObj->ExecuteQuery('Multiple');

	echo json_encode($tipo_documento);

?>