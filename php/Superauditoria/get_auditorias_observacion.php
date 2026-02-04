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

    $query = 'SELECT    
    FP.Imagen as Imagen1,
    FA.Imagen as Imagen2, 
    DATE_FORMAT(NOW(),"%Y-%m-%d") AS Hoy, 
    A.Fecha_Auditoria,
    A.Id_Auditoria,    
    A.Id_Dispensacion,D.Codigo,
    CONCAT(D.Codigo) as DIS, (SELECT CONCAT(S.Nombre," - ",T.Nombre) FROM Tipo_Servicio T INNER JOIN Servicio S ON T.Id_Servicio=S.Id_Servicio WHERE T.Id_Tipo_Servicio=A.Id_Tipo_Servicio ) as  Servicio,   A.Archivo ,    DATE(A.Fecha_Preauditoria) as Fecha, D.Estado_Facturacion, D.Estado_Dispensacion   
    FROM Auditoria A
    LEFT JOIN Funcionario FP 
    ON A.Funcionario_Preauditoria=FP.Identificacion_Funcionario
    LEFT JOIN Funcionario FA 
    ON A.Funcionario_Auditoria=FA.Identificacion_Funcionario
    INNER JOIN Dispensacion D
    ON A.Id_Dispensacion=D.Id_Dispensacion
    LEFT  JOIN (SELECT PD.Id_Punto_Dispensacion, D.Id_Departamento FROM Punto_Dispensacion PD INNER JOIN Departamento D ON PD.Departamento=D.Id_Departamento) PT ON A.Punto_Pre_Auditoria=PT.Id_Punto_Dispensacion
    WHERE A.Estado="Con Observacion" AND DATE(D.Fecha_Actual) > "2021-08-01"
    
    '.$condicion.' 
    ORDER BY A.Id_Auditoria DESC';

    

$query_count = '
SELECT COUNT(*) AS Total

FROM Auditoria A
LEFT JOIN Funcionario FP 
ON A.Funcionario_Preauditoria=FP.Identificacion_Funcionario
LEFT JOIN Funcionario FA 
ON A.Funcionario_Auditoria=FA.Identificacion_Funcionario
INNER JOIN Dispensacion D
ON A.Id_Dispensacion=D.Id_Dispensacion
INNER JOIN (SELECT PD.Id_Punto_Dispensacion, D.Id_Departamento FROM Punto_Dispensacion PD INNER JOIN Departamento D ON PD.Departamento=D.Id_Departamento) PT ON A.Punto_Pre_Auditoria=PT.Id_Punto_Dispensacion
WHERE A.Estado="Con Observacion" AND DATE(D.Fecha_Actual) > "2021-08-01" '.$condicion;    

$paginationData = new PaginacionData($tam, $query_count, $pag);

//Se crea la instancia que contiene la consulta a realizar
$queryObj = new QueryBaseDatos($query);

//Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
$auditorias = $queryObj->Consultar('Multiple', true, $paginationData);

echo json_encode($auditorias);
function SetCondiciones($req){
    $condicion = '';
    if (isset($_REQUEST['cod']) && $_REQUEST['cod'] != "") {
       
            $condicion .= "AND  A.Id_Auditoria='".str_replace("AUD00","",$_REQUEST['cod'])."'";
        
    
    }
    if (isset($_REQUEST['dis']) && $_REQUEST['dis'] != "") {
            $condicion .= "AND  D.Codigo='".$_REQUEST['dis']."'";
    }
    if(isset($_REQUEST['tipo']) && $_REQUEST['tipo'] != ""){
        $tipo=$_REQUEST['tipo'];
     
        $condicion.=" AND A.Id_Tipo_Servicio=$tipo  ";
       
    }
    if(isset($_REQUEST['dep']) && $_REQUEST['dep'] != ""){
        $dep=$_REQUEST['dep'];       
        $condicion.=" AND PT.Id_Departamento IN ($dep)  ";
       
    }


    return $condicion;
}
?>