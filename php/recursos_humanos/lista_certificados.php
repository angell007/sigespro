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



// $query = 'SELECT CL.*, CONCAT(F.Nombres," ", F.Apellidos) as Funcionario, F.Imagen  FROM Certificado_Laboral CL INNER JOIN Funcionario F ON CL.Identificacion_Funcionario=F.Identificacion_Funcionario ORDER BY CL.Id_Certificado_Laboral DESC  ';
$query = 'SELECT CL.*, CONCAT(F.Nombres," ", F.Apellidos) as funcionario, F.Imagen, DATE_ADD(CURDATE(), INTERVAL - 30 DAY) AS ULTIMOS
            FROM Certificado_Laboral CL 
            INNER JOIN Funcionario F ON CL.Identificacion_Funcionario=F.Identificacion_Funcionario
            WHERE DATE_FORMAT(CL.Fecha, "%Y-%m-%d") >=  DATE_ADD(CURDATE(), INTERVAL - 30 DAY)
            ORDER BY CL.Id_Certificado_Laboral DESC';

$query_count = 'SELECT 
        COUNT(CL.Id_Certificado_Laboral) AS Total
        FROM Certificado_Laboral CL INNER JOIN Funcionario F ON CL.Identificacion_Funcionario=F.Identificacion_Funcionario
    ';

$paginationData = new PaginacionData($tam, $query_count, $pag);
$queryObj = new QueryBaseDatos($query);
$certificados = $queryObj->Consultar('Multiple', true, $paginationData);

echo json_encode($certificados);



?>