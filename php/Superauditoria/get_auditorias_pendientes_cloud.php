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

$pag = (isset($_REQUEST['pag']) ? $_REQUEST['pag'] : '');
$tam = (isset($_REQUEST['tam']) ? $_REQUEST['tam'] : '');

$hoy = date('Y-m-d');
$ultimos_dos_meses = strtotime('-2 month', strtotime($hoy));
$ultimos_dos_meses = date('Y-m-01', $ultimos_dos_meses);
$condicion_fechas = '';



$condicion = SetCondiciones($_REQUEST);

$query = 'SELECT    
    FP.Imagen as Imagen1,
    D.Numero_Documento,
    FA.Imagen as Imagen2, 
    DATE_FORMAT(NOW(),"%Y-%m-%d") AS Hoy, 
    A.Fecha_Auditoria,
    A.Id_Auditoria,    
    A.Id_Dispensacion,
    D.Codigo as DIS,(SELECT CONCAT(S.Nombre," - ",T.Nombre) FROM Tipo_Servicio T 
    INNER JOIN Servicio S ON T.Id_Servicio=S.Id_Servicio WHERE T.Id_Tipo_Servicio=A.Id_Tipo_Servicio ) as  Servicio,
    A.Archivo, 
    DATE(A.Fecha_Preauditoria) as Fecha   
    FROM Auditoria A
    LEFT JOIN Tipo_Servicio TSE ON A.Id_Tipo_Servicio = TSE.Id_Tipo_Servicio
    LEFT JOIN Funcionario FP 
    ON A.Funcionario_Preauditoria=FP.Identificacion_Funcionario
    LEFT JOIN Funcionario FA 
    ON A.Funcionario_Auditoria=FA.Identificacion_Funcionario
    INNER JOIN Dispensacion D
    ON A.Id_Dispensacion=D.Id_Dispensacion
    
    left JOIN (SELECT PD.Id_Punto_Dispensacion, D.Id_Departamento 
    FROM Punto_Dispensacion PD 
    INNER JOIN Departamento D ON PD.Departamento=D.Id_Departamento) PT ON A.Punto_Pre_Auditoria=PT.Id_Punto_Dispensacion
    WHERE A.Estado="Pre Auditado" 
    AND DATE(D.Fecha_Actual) > "2021-08-01"
          AND TSE.Auditoria=1 
          AND D.Estado_Dispensacion!="Anulada" 
          AND
            (A.Id_Dispensacion IS NOT NULL  )  

    ' . $condicion . ' 
    ORDER BY A.Id_Auditoria DESC';
    $query_count = 'SELECT COUNT(*) AS Total

                FROM Auditoria A
                LEFT JOIN Funcionario FP 
                ON A.Funcionario_Preauditoria=FP.Identificacion_Funcionario
                LEFT JOIN Funcionario FA 
                ON A.Funcionario_Auditoria=FA.Identificacion_Funcionario
                INNER JOIN Dispensacion D ON A.Id_Dispensacion=D.Id_Dispensacion
                left JOIN (SELECT PD.Id_Punto_Dispensacion, D.Id_Departamento 
    FROM Punto_Dispensacion PD 
    INNER JOIN Departamento D ON PD.Departamento=D.Id_Departamento) PT ON A.Punto_Pre_Auditoria=PT.Id_Punto_Dispensacion
                WHERE A.Estado="Pre Auditado" AND DATE(D.Fecha_Actual) > "2021-08-01" AND D.Estado_Dispensacion!="Anulada" AND
                            (   A.Id_Dispensacion IS NOT NULL  )  
                ' . $condicion;

//echo $query_count;exit;
$paginationData = new PaginacionData($tam, $query_count, $pag);

//Se crea la instancia que contiene la consulta a realizar
$queryObj = new QueryBaseDatos($query);

//Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
$auditorias = $queryObj->Consultar('Multiple', true, $paginationData);

echo json_encode($auditorias);


function SetCondiciones($req)
{

    global $condicion_fechas, $ultimos_dos_meses, $hoy;
    $condicion = '';


    if (isset($_REQUEST['doc']) && $_REQUEST['doc'] != "") {
        $condicion .= "AND D.Numero_Documento LIKE '%$_REQUEST[doc]%' ";
    }
    if (isset($_REQUEST['dis']) && $_REQUEST['dis'] != "") {
        $condicion .= "AND  D.Codigo='" . $_REQUEST['dis'] . "'";
    }
    if (isset($_REQUEST['tipo']) && $_REQUEST['tipo'] != "") {
        $tipo = $_REQUEST['tipo'];
        $condicion .= " AND A.Id_Tipo_Servicio=$tipo  ";
    }
    if (isset($_REQUEST['dep']) && $_REQUEST['dep'] != "") {
        $dep = $_REQUEST['dep'];
        $condicion .= " AND PT.Id_Departamento IN ($dep)  ";
    }

    if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "") {
        $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
        $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);

        $condicion .= "AND  DATE_FORMAT(D.Fecha_Actual,'%Y-%m-%d') BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    } else {
        $fecha_fin = date('Y-m-d');
        $fecha_inicio = date('Y-m-d');
    }

    return $condicion;
}
