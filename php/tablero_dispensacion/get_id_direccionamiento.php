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
$http_response = new HttpResponse();

$num= ( isset( $_REQUEST['num'] ) ? $_REQUEST['num'] : '' );
$idPaciente= ( isset( $_REQUEST['pac'] ) ? $_REQUEST['pac'] : '' );

$query=GetQueryId();
$queryObj->SetQuery($query);
$idDireccionamiento = $queryObj->ExecuteQuery('simple');

if($idDireccionamiento){
	$query= GetQuery();
	$queryObj->SetQuery($query);
	$direccionamientos = $queryObj->ExecuteQuery('Multiple');
	
	foreach ($direccionamientos as $key => $value) {
		if($value['Tipo_Tecnologia']!='M'){
			$direccionamientos[$key]['Asociados']=(ARRAY)GetAsociados($value['Tipo_Tecnologia'],$value['CodSerTecAEntregar'], $value);
		}else{
		    $direccionamientos[$key]['Asociados']=[];
		}
	}
}else{
	$direccionamientos=[];
}



if(count($direccionamientos)>0){
	$http_response->SetRespuesta(0, 'Se Obtuvieron datos', '');
	$response = $http_response->GetRespuesta();
	$response['Productos']=$direccionamientos;
}else{
	$http_response->SetRespuesta(1, 'No se Obtuvieron datos', '');
	$response = $http_response->GetRespuesta();
}



echo json_encode($response);

function SetCondiciones(){
	global $serv, $id_punto;



	$condicion=" WHERE TS.Id_Servicio=$serv AND TSPD.Id_Punto_Dispensacion = $id_punto"; 

	return $condicion; 
}

function GetQuery(){
	global $idPaciente,$num,$idDireccionamiento;
    
	$fecha=date('Y-m-d');
    /*CAMBIOS CARLOS CARDONA 18-08-2020 - COSTO*/
	$query=" SELECT D.*, DATE_SUB(Fecha_Maxima_Entrega, INTERVAL 31 DAY) as Resta, DATE_SUB(Fecha_Maxima_Entrega, INTERVAL 5 DAY) as Maxima_Fecha, PD.Cantidad, P.Nombre_Comercial,	CONCAT(P.Principio_Activo,' ',P.Presentacion,' ',P.Concentracion,' ', P.Cantidad,' ', P.Unidad_Medida) as Nombre,
	P.Codigo_Cum,PD.Cantidad, '0' as Seleccionado, PD.Tipo_Tecnologia,PD.CodSerTecAEntregar, IFNULL( (SELECT CP.Costo_Promedio FROM Costo_Promedio CP WHERE CP.Id_Producto = PD.Id_Producto), 0 ) AS Costo
	FROm Dispensacion_Mipres D
	INNER JOIN Producto_Dispensacion_Mipres PD ON D.Id_Dispensacion_Mipres=PD.Id_Dispensacion_Mipres
	INNER JOIN Producto P ON PD.Id_Producto = P.Id_Producto WHERE PD.Id_Dispensacion_Mipres=$idDireccionamiento[Id_Dispensacion_Mipres] ";

	return $query;
}

function GetAsociados($tipo,$cum,$data){
    /*CAMBIOS CARLOS CARDONA 18-08-2020 - COSTO*/
	$query="SELECT P.Nombre_Comercial,	CONCAT(P.Principio_Activo,' ',P.Presentacion,' ',P.Concentracion,' ', P.Cantidad,' ', P.Unidad_Medida) as Nombre,	P.Codigo_Cum, P.Id_Producto,
	$data[Cantidad] as Cantidad, 0 as Seleccionado, 'No' AS Ver_Asociado, P.Id_Producto as value, CONCAT_WS(' ', P.Nombre_Comercial, P.Principio_Activo,P.Presentacion,P.Concentracion, P.Cantidad,P.Unidad_Medida) as label ,
	IFNULL( (SELECT CP.Costo_Promedio FROM Costo_Promedio CP WHERE CP.Id_Producto = PD.Id_Producto), 0 ) AS Costo

	FROM Producto_Tipo_Tecnologia_Mipres PD 
	INNER JOIN Tipo_Tecnologia_Mipres M ON PD.Id_Tipo_Tecnologia_Mipres=M.Id_Tipo_Tecnologia_Mipres 
	INNER JOIN Producto P ON PD.Id_Producto = P.Id_Producto   
	WHERE (Codigo_Actual='$cum' OR Codigo_Anterior='$cum') AND M.Codigo='$tipo'";
	$oCon = new consulta();
	$oCon->setQuery($query);
	$oCon->setTipo('Multiple');
	$prod = $oCon->getData();
	unset($oCon);

	return $prod;
}
function GetQueryId(){

	global $idPaciente,$num;
	$query=" SELECT  D.Id_Dispensacion_Mipres
	FROm Dispensacion_Mipres D
	INNER JOIN Producto_Dispensacion_Mipres PD ON D.Id_Dispensacion_Mipres=PD.Id_Dispensacion_Mipres
	INNER JOIN Producto P ON PD.Id_Producto = P.Id_Producto WHERE Id_Paciente='$idPaciente' AND  PD.IDDireccionamiento='$num' AND NOT EXISTS(SELECT Id_Dispensacion FROM Dispensacion WHERE Estado_Dispensacion != 'Anulada' AND Id_Tipo_Servicio != 7 AND Id_Dispensacion_Mipres =  D.Id_Dispensacion_Mipres) AND (D.Estado='Pendiente' OR D.Estado='Programado')  
	  ";

	return $query;
}




?>