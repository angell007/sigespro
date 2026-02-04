<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.paginacion.php');
include_once('../../class/class.http_response.php');
include_once('../../class/class.utility.php');

$condicion = SetCondiciones($_REQUEST);
$pag = ( isset( $_REQUEST['pag'] ) ? $_REQUEST['pag'] : '' );
$tam = ( isset( $_REQUEST['tam'] ) ? $_REQUEST['tam'] : '' );

$query = 'SELECT T.* FROM Tipo_Documento T '.$condicion;

$query_count = '
SELECT 
    COUNT(T.Id_Tipo_Documento) AS Total
FROM Tipo_Documento T
'.$condicion;


$paginationData = new PaginacionData($tam, $query_count, $pag);
$queryObj = new QueryBaseDatos($query);
$turneros = $queryObj->Consultar('Multiple', true, $paginationData);
echo json_encode($turneros);


function SetCondiciones($req){
    
    $condicion='';
    if (isset($req['nom']) && $req['nom']) {
          $condicion .= " WHERE  T.Nombre LIKE '%".$req['nom']."%'";        
    }
    if (isset($req['cod']) && $req['cod']) {
        if ($condicion != "") {
            $condicion .= " AND T.Codigo LIKE '%".$req['cod']."%'";
        } else {
            $condicion .= " WHERE T.Codigo LIKE '%".$req['cod']."%'";
        }
    }

    return $condicion;
}
?>