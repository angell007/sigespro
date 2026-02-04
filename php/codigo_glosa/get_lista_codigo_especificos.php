<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

date_default_timezone_set('America/Bogota');

require_once('../../config/start.inc.php');
include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.paginacion.php');
include_once('../../class/class.http_response.php');
include_once('../../class/class.consulta.php');

$http_response = new HttpResponse();

$pag = ( isset( $_REQUEST['pag'] ) ? $_REQUEST['pag'] : '' );
$tam = ( isset( $_REQUEST['tam'] ) ? $_REQUEST['tam'] : '' );

$condicion = SetCondiciones($_REQUEST);

$query = ' SELECT *, Id_Codigo_General_Glosa as Codigo_General FROM  Codigo_Especifico_Glosa
'.$condicion.' ORDER BY Id_Codigo_Especifico_Glosa ASC ';


$query_count = 'SELECT COUNT(*) AS Total
FROM Codigo_Especifico_Glosa 
'.$condicion.'';    

$paginationData = new PaginacionData($tam, $query_count, $pag);

//Se crea la instancia que contiene la consulta a realizar
$queryObj = new QueryBaseDatos($query);

//Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
$codigos = $queryObj->Consultar('Multiple', true, $paginationData);

echo json_encode($codigos);
function SetCondiciones($req){
    $condicion = '';
    if (isset($_REQUEST['concepto']) && $_REQUEST['concepto'] != "") {       
            $condicion .= " WHERE  Concepto LIKE '%$_REQUEST[concepto]%' ";
    }
    
    return $condicion;
}
?>