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
    CONCAT_WS(" ",P.Primer_Nombre,P.Primer_Apellido) as Paciente, 
    A.Estado, 
    (SELECT Nombre FROM Punto_Dispensacion WHERE Id_Punto_Dispensacion=A.Punto_Pre_Auditoria) as Nombre_Punto, 
    CONCAT_WS(" ", FP.Nombres,FP.Apellidos) as Nombre_Funcionario_Preauditoria, 
    FP.Imagen as Imagen1,
    A.Fecha_Preauditoria, 
    DATE_FORMAT(NOW(),"%Y-%m-%d") AS Hoy, 
    A.Fecha_Auditoria,
    A.Id_Auditoria,
    (SELECT CONCAT(S.Nombre," - ",T.Nombre) FROM Tipo_Servicio T INNER JOIN Servicio S ON T.Id_Servicio=S.Id_Servicio WHERE T.Id_Tipo_Servicio=A.Id_Tipo_Servicio ) as TipoServicio,
    A.Id_Dispensacion,
    D.Codigo as DIS
    FROM Auditoria A
    LEFT JOIN Funcionario FP 
    ON A.Funcionario_Preauditoria=FP.Identificacion_Funcionario    
    INNER JOIN Dispensacion D
    ON A.Id_Dispensacion=D.Id_Dispensacion
    INNER JOIN Paciente P
    ON D.Numero_Documento=P.Id_Paciente 
    WHERE A.Estado="Aceptar"
    '.$condicion.'
    ORDER BY A.Id_Auditoria DESC ';

    $query_count = '
    SELECT COUNT(*) AS Total
    FROM Auditoria A
    LEFT JOIN Funcionario FP 
    ON A.Funcionario_Preauditoria=FP.Identificacion_Funcionario    
    INNER JOIN Dispensacion D
    ON A.Id_Dispensacion=D.Id_Dispensacion
    INNER JOIN Paciente P
    ON D.Numero_Documento=P.Id_Paciente 
    WHERE A.Estado="Aceptar"
    '.$condicion;    

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


    return $condicion;
}
?>