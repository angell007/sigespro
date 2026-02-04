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

$query = 'SELECT M.* , F.Imagen, UPPER(CONCAT(F.Nombres, " ", F.Apellidos)) as Funcionario ,
        SUM( IFNULL( OM.Valor_Medicamentos , 0) ) AS Medicamento,
        SUM( IFNULL( OM.Valor_Materiales  , 0 ) ) AS Material
        FROM Metas M
        INNER JOIN Metas_Zonas MZ ON MZ.Id_Meta = M.Id_Metas
        INNER JOIN Objetivos_Meta OM ON OM.Id_Metas_Zonas = MZ.Id_Metas_Zonas
        INNER JOIN Funcionario F ON M.Identificacion_Funcionario=F.Identificacion_Funcionario
'.$condicion.'
GROUP BY M.Id_Metas
ORDER BY Anio DESC 
';

$query_count = "SELECT COUNT(*)  AS Total
FROM (Select M.Id_Metas From Metas M
INNER JOIN Metas_Zonas MZ ON MZ.Id_Meta = M.Id_Metas
INNER JOIN Objetivos_Meta OM ON OM.Id_Metas_Zonas = MZ.Id_Metas_Zonas
INNER JOIN Funcionario F ON M.Identificacion_Funcionario=F.Identificacion_Funcionario
$condicion
GROUP BY M.Id_Metas
ORDER BY Anio DESC 
)M 
";
$paginationData = new PaginacionData($tam, $query_count, $pag);

$queryObj = new QueryBaseDatos($query);
$metas = $queryObj->Consultar('Multiple', true, $paginationData);

echo json_encode($metas);

function SetCondiciones($req){
    

    $condicion = ''; 

    if (isset($req['rep']) && $req['rep']) {       
            $condicion .= " WHERE CONCAT(F.Nombres,' ',F.Apellidos) LIKE '%".$req['rep']."%'";        
    }    

   /*  if (isset($req['zona']) && $req['zona']) {
        if ($condicion != "") {
            $condicion .= " AND Z.Nombre LIKE '%".$req['zona']."%'";
        } else {
            $condicion .= " WHERE Z.Nombre LIKE '%".$req['zona']."%'";
        }
    }    
 */
    return $condicion;
}
      

?>