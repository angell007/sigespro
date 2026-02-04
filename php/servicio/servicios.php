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

	$pag = ( isset( $_REQUEST['pag'] ) ? $_REQUEST['pag'] : '' );
	$tam = ( isset( $_REQUEST['tam'] ) ? $_REQUEST['tam'] : '' );

    $condicion = SetCondiciones($_REQUEST);

    $query = ' SELECT * FROM Servicio 
    '.$condicion.'
    ORDER BY Nombre DESC ';

    $query_count = '
    SELECT COUNT(*) AS Total
    FROM Servicio
    '.$condicion;    

$paginationData = new PaginacionData($tam, $query_count, $pag);

//Se crea la instancia que contiene la consulta a realizar
$queryObj = new QueryBaseDatos($query);

//Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
$auditorias = $queryObj->Consultar('Multiple', true, $paginationData);

echo json_encode($auditorias);
function SetCondiciones($req){
    $condicion = '';
    if (isset($_REQUEST['nom']) && $_REQUEST['nom'] != "") {
       
            $condicion .= " WHERE Nombre LIKE '$_REQUEST[nom]%'";
    }
   


    return $condicion;
}
?>