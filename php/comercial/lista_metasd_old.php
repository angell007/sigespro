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

$query = 'SELECT M.*,F.Imagen, UPPER(CONCAT(F.Nombres, " ", F.Apellidos)) as Funcionario,  Z.Nombre as Zona,(SELECT SUM(MC.Valor_Medicamento) FROM Meta_Cliente MC WHERE MC.Id_Meta=M.Id_Meta) as Medicamento, (SELECT SUM(MC.Valor_Material) FROM Meta_Cliente MC WHERE MC.Id_Meta=M.Id_Meta) as Material
FROM Meta M
INNER JOIN Funcionario F
On M.Identificacion_Funcionario=F.Identificacion_Funcionario
INNER JOIN Zona Z ON M.Id_Zona=Z.Id_Zona
'.$condicion;

$query_count = 'SELECT COUNT(*) AS Total FROM Meta M
INNER JOIN Funcionario F ON M.Identificacion_Funcionario=F.Identificacion_Funcionario
INNER JOIN Zona Z ON M.Id_Zona=Z.Id_Zona '.$condicion;
$paginationData = new PaginacionData($tam, $query_count, $pag);
$queryObj = new QueryBaseDatos($query);
$metas = $queryObj->Consultar('Multiple', true, $paginationData);

echo json_encode($metas);

function SetCondiciones($req){
    

    $condicion = ''; 

    if (isset($req['rep']) && $req['rep']) {       
            $condicion .= " WHERE CONCAT(F.Nombres,' ',F.Apellidos) LIKE '%".$req['rep']."%'";        
    }    

    if (isset($req['zona']) && $req['zona']) {
        if ($condicion != "") {
            $condicion .= " AND Z.Nombre LIKE '%".$req['zona']."%'";
        } else {
            $condicion .= " WHERE Z.Nombre LIKE '%".$req['zona']."%'";
        }
    }    

    return $condicion;
}
      

?>