<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.http_response.php');
include_once('../../class/class.utility.php');


$http_response = new HttpResponse();
$queryObj = new QueryBaseDatos();



$query= GetQueryServicios();
$query_tipo_servicios=GetQueryTipoServicio();

$queryObj->SetQuery($query);
$servicios = $queryObj->ExecuteQuery('Multiple');

$queryObj->SetQuery($query_tipo_servicios);
$tipo_servicios = $queryObj->ExecuteQuery('Multiple');

$resultado['Servicio']=$servicios;
$resultado['Tipo_Servicio']=$tipo_servicios;
echo json_encode($resultado);


function GetQueryServicios(){
	global $condicion;

  
        $query="SELECT T.Id_Servicio, T.Nombre as Nombre FROM Servicio T Order BY Nombre ASC";
    

	return $query;
}

function GetQueryTipoServicio(){
    $query="SELECT T.Id_Tipo_Servicio, CONCAT(S.Nombre,' - ',T.Nombre) as Nombre, T.Nombre AS Nombre_Tipo_Servicio FROM Tipo_Servicio T INNER JOIN Servicio S on T.Id_Servicio=S.Id_Servicio ";

    return $query;
}





?>