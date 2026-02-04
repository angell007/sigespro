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
  
$query= GetQuery();

$queryObj->SetQuery($query);
$servicios = $queryObj->ExecuteQuery('Multiple');



echo json_encode($servicios);



function GetQuery(){
	global $condicion;

	$query='SELECT Nombre FROM Causal_No_Pago  ';

	return $query;
}




?>