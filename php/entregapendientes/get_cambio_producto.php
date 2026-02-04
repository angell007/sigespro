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

    $query = ' SELECT PD.*,DATE(PD.Fecha) as Fecha, AR.Fecha as Fecha_Recibido, IFNULL(CONCAT(P.Principio_Activo," ",P.Presentacion," ",P.Concentracion," ", P.Cantidad," ", P.Unidad_Medida),CONCAT(P.Nombre_Comercial," ",P.Laboratorio_Comercial)) as Nombre_Producto,P.Nombre_Comercial,CONCAT_WS(" ",PA.Primer_Nombre,PA.Segundo_Nombre,PA.Primer_Apellido,PA.Segundo_Apellido )  as Paciente, D.Codigo as DIS, P.Codigo_Cum
      
    FROM Descarga_Pendiente_Remision DP
    INNER JOIN Producto_Descarga_Pendiente_Remision PD
    ON DP.Id_Descarga_Pendiente_Remision=PD.Id_Descarga_Pendiente_Remision 
    INNER JOIN Producto P ON PD.Id_Producto=P.Id_Producto   
    INNER JOIN Dispensacion D ON PD.Id_Dispensacion=D.Id_Dispensacion
    INNER JOIN Paciente PA ON PD.Id_Paciente=PA.Id_Paciente
    INNER JOIN Remision R ON PD.Id_Remision=R.Id_Remision 
    INNER JOIN (SELECT DATE_ADD(DATE(Fecha), INTERVAL 20 DAY) as Fecha, Id_Remision FROM Actividad_Remision WHERE Estado="Recibida") AR ON R.Id_Remision=AR.Id_Remision
    WHERE PD.Entregado="No" AND R.Estado="Recibida" AND AR.Fecha<CURDATE() AND D.Estado_Facturacion!="Facturada" 

    '.$condicion.' ORDER BY AR.Fecha ASC';

  


$query_count = 'SELECT COUNT(*) AS Total
    FROM Descarga_Pendiente_Remision DP
    INNER JOIN Producto_Descarga_Pendiente_Remision PD
    ON DP.Id_Descarga_Pendiente_Remision=PD.Id_Descarga_Pendiente_Remision 
    INNER JOIN Producto P ON PD.Id_Producto=P.Id_Producto   
    INNER JOIN Dispensacion D ON PD.Id_Dispensacion=D.Id_Dispensacion
    INNER JOIN Paciente PA ON PD.Id_Paciente=PA.Id_Paciente
    INNER JOIN Remision R ON PD.Id_Remision=R.Id_Remision 
    INNER JOIN (SELECT DATE_ADD(DATE(Fecha), INTERVAL 20 DAY) as Fecha, Id_Remision FROM Actividad_Remision WHERE Estado="Recibida") AR ON R.Id_Remision=AR.Id_Remision
    WHERE PD.Entregado="No" AND R.Estado="Recibida" AND AR.Fecha<CURDATE()
'.$condicion.'';    

$paginationData = new PaginacionData($tam, $query_count, $pag);

//Se crea la instancia que contiene la consulta a realizar
$queryObj = new QueryBaseDatos($query);

//Ejecuta la consulta de la instancia queryobj y retorna el resultado de la misma segun los parametros
$productos = $queryObj->Consultar('Multiple', true, $paginationData);

echo json_encode($productos);
function SetCondiciones($req){
    $condicion = '';
    if (isset($_REQUEST['Id_Pac']) && $_REQUEST['Id_Pac'] != "") {       
            $condicion .= "AND  PD.Id_Paciente=".$_REQUEST['Id_Pac']."";
    }
    if (isset($_REQUEST['dis']) && $_REQUEST['dis'] != "") {        
            $condicion .= "AND  D.Codigo='".$_REQUEST['dis']."'";
    
    }
    if (isset($_REQUEST['punto']) && $_REQUEST['punto'] != "") {        
            $condicion .= "AND  DP.Id_Punto_Dispensacion=".$_REQUEST['punto']."";
    
    }
    if (isset($_REQUEST['Pac']) && $_REQUEST['Pac'] != "") {        
            $condicion .= "AND  CONCAT_WS(' ',PA.Primer_Nombre,PA.Segundo_Nombre,PA.Primer_Apellido,PA.Segundo_Apellido )LIKE '%".$_REQUEST['Pac']."%'";
    
    }
    if (isset($_REQUEST['Prod']) && $_REQUEST['Prod'] != "") {        
            $condicion .= "AND  P.Nombre_Coomercial LIKE '%".$_REQUEST['Prod']."%'";
    
    }


    return $condicion;
}
?>