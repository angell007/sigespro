<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.paginacion.php');
include_once('../../class/class.http_response.php');
include_once('../../class/class.utility.php');


$pag = ( isset( $_REQUEST['pag'] ) ? $_REQUEST['pag'] : '' );
$tam = ( isset( $_REQUEST['tam'] ) ? $_REQUEST['tam'] : '' );

$condicion = SetCondiciones($_REQUEST);


$query='SELECT C.*, CONCAT(F.Nombres," ", F.Apellidos) as Funcionario_Envio, F.Imagen
From Correspondencia C
INNER JOIN Funcionario F
ON C.Id_Funcionario_Envia=F.Identificacion_Funcionario '.$condicion.' Order BY C.Fecha_Envio DESC, C.Estado ASC';

$query_count = 'SELECT 
    COUNT(C.Id_Correspondencia) AS Total
    From Correspondencia C
    INNER JOIN Funcionario F
    ON C.Id_Funcionario_Envia=F.Identificacion_Funcionario
'.$condicion;

$paginationData = new PaginacionData($tam, $query_count, $pag);
$queryObj = new QueryBaseDatos($query);
$correspondencia = $queryObj->Consultar('Multiple', true, $paginationData);

echo json_encode($correspondencia);

function SetCondiciones($req){
    
    $condicion=''; 
    $condicion .= 'WHERE Punto_Envio = "' . $req['punto'].'"';
     

    return $condicion;
}
?>