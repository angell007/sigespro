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
    $having='';
	$pag = ( isset( $_REQUEST['pag'] ) ? $_REQUEST['pag'] : '' );
	$tam = ( isset( $_REQUEST['tam'] ) ? $_REQUEST['tam'] : '' );

    $condicion = SetCondiciones($_REQUEST);

    $query = ' SELECT *       
    FROM Prioridad_Turnero 
    '.$condicion.' Order BY Nombre DESC';

 

$query_count = '
SELECT COUNT(*) AS Total

FROM Prioridad_Turnero 
'.$condicion;    



$paginationData = new PaginacionData($tam, $query_count, $pag);

//Se crea la instancia que contiene la consulta a realizar
$queryObj = new QueryBaseDatos($query);

//Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
$prioridad = $queryObj->Consultar('Multiple', true, $paginationData);

echo json_encode($prioridad);
function SetCondiciones($req){
    $condicion = '';    


    if(isset($_REQUEST['nombre']) && $_REQUEST['nombre'] != ""){
        if($condicion!=''){
         $condicion .= "AND  Nombre LIKE'%".$_REQUEST['nombre']."%'";
        }else{
         $condicion .= "WHERE  Nombre LIKE '%".$_REQUEST['nombre']."%'";
        }
     }


    return $condicion;
}
?>