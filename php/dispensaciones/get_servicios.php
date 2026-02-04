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
$punto = ( isset( $_REQUEST['id_punto'] ) ? $_REQUEST['id_punto'] : '' );

$condicion=SetCondiciones(); 

$query= GetQuery();


$queryObj->SetQuery($query);
$servicios = $queryObj->ExecuteQuery('Multiple');


echo json_encode($servicios);

function SetCondiciones(){
    global $punto;
    $condicion='';
    if($punto!=''){
        $condicion='WHERE P.Id_Punto_Dispensacion ='.$punto;    
    }
    return $condicion;
}

function GetQuery(){
	global $condicion;

    if($condicion!=''){
        $query="SELECT T.Id_Tipo_Servicio, CONCAT(S.Nombre,' - ',T.Nombre) as Nombre, T.Nombre AS Nombre_Tipo_Servicio 
        FROM Tipo_Servicio_Punto_Dispensacion P
        INNER JOIN Tipo_Servicio T ON P.Id_Tipo_Servicio=T.Id_Tipo_Servicio
        INNER JOIN Servicio S on T.Id_Servicio=S.Id_Servicio $condicion ";
    }else{
        $query="SELECT T.Id_Tipo_Servicio, CONCAT(S.Nombre,' - ',T.Nombre) as Nombre, T.Nombre AS Nombre_Tipo_Servicio FROM Tipo_Servicio T INNER JOIN Servicio S on T.Id_Servicio=S.Id_Servicio ";
    }

	return $query;
}





?>