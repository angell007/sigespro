<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.http_response.php');
include_once('../../class/class.utility.php');


$http_response = new HttpResponse();
$queryObj = new QueryBaseDatos();
$util = new Utility();

$colores=['bg-c-blue','bg-info','bg-inverse','bg-c-pink','bg-c-lite-green','bg-default','bg-facebook'];

$iconos_servicios=['ti ti-tag','ti ti-receipt'];

$condicion=SetCondiciones(); 

$query= GetQueryConteoTotal();

$respuesta=[];

$queryObj->SetQuery($query);
$conteo_total = $queryObj->ExecuteQuery('simple');

array_push($respuesta,$conteo_total);

$servicios=GetServicios();

$i=0;
foreach ($servicios as $key => $value) {
    $query=GetQueryServicios($value, $i);
    $queryObj->SetQuery($query);
    $conteo = $queryObj->ExecuteQuery('simple');
    array_push($respuesta,$conteo);

   $i++;
}

$query="SELECT COUNT(*) as Total, '$colores[$i]' as class , 'fa fa-hourglass-end' as icono, 'Dis. Pendientes'  as Titulo FROM Dispensacion"
.$condicion." AND Pendientes > 0 ";

$queryObj->SetQuery($query);
$conteo = $queryObj->ExecuteQuery('simple');
array_push($respuesta,$conteo);
$i++;

$query="SELECT COUNT(*) as Total, '$colores[$i]' as class , 'fa fa-file-text-o' as icono, 'Dis. Facturadas'  as Titulo FROM Dispensacion"
.$condicion." AND Estado_Facturacion='Facturada' ";

$queryObj->SetQuery($query);
$conteo = $queryObj->ExecuteQuery('simple');
array_push($respuesta,$conteo);


echo json_encode($respuesta);

function SetCondiciones(){
    
    $condicion='';

    if($_REQUEST['fecha']){
        $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
        $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
        
        $condicion .= " WHERE DATE(Fecha_Actual) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    }else{
        $condicion=" WHERE DATE(Fecha_Actual)=CURRENT_DATE()";
    }

    if($_REQUEST['id_punto']){
         
        $condicion .= " AND Id_Punto_Dispensacion=$_REQUEST[id_punto]";
    }

return $condicion;
}

function GetQueryConteoTotal(){
	global $condicion;

	$query='SELECT COUNT(*) as Total, "bg-c-yellow" as class , "fa fa-ticket" as icono, "Total Dis" as Titulo FROM Dispensacion'
		.$condicion;

	return $query;
}

function GetServicios(){
    global $queryObj;

    $query='SELECT Nombre as Servicio, Id_Servicio FROM Servicio WHERE Estado="Activo" ';  
    $queryObj->SetQuery($query);
    $servicios = $queryObj->ExecuteQuery('Multiple');

    return $servicios;
}

function GetQueryServicios($value, $pos){
    global $condicion,$colores, $iconos_servicios;

    $pos_icono=$pos%2;
    

    $query="SELECT COUNT(*) as Total, '$colores[$pos]' as class ,' $iconos_servicios[$pos_icono]' as icono, 'Dis. $value[Servicio]' as Titulo FROM Dispensacion"
    .$condicion." AND Id_Servicio =$value[Id_Servicio] ";


    return $query;
}



?>