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

$id_tipo_servicio = ( isset( $_REQUEST['id_tipo_servicio'] ) ? $_REQUEST['id_tipo_servicio'] : '' );

$query= GetTipoServicio();

$queryObj->SetQuery($query);

$tiposervicio = $queryObj->ExecuteQuery('simple');

echo json_encode($tiposervicio);

function GetTipoServicio(){
	global $id_tipo_servicio;

	$query="SELECT * 
                FROM Tipo_Servicio 
            WHERE Id_Tipo_Servicio=$id_tipo_servicio";
	return $query;
}