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
			$direccionamientos[$key]['Asociados']=GetAsociados($value['Tipo_Tecnologia'],$value['CodSerTecAEntregar'], $value);
			
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

function GetQuery(){

	global $idPaciente,$num, $idDireccionamiento;

	$fecha=date('Y-m-d');

	$query=" SELECT PD.Id_Producto_Dispensacion_Mipres, 
				    DATE_SUB(Fecha_Maxima_Entrega, INTERVAL 31 DAY) as Resta, 
					DATE_SUB(Fecha_Maxima_Entrega, INTERVAL 7 DAY) as Maxima_Fecha,PD.Id_Dispensacion_Mipres, 
					CONCAT(P.Principio_Activo,' ',P.Presentacion,' ',P.Concentracion,' ', P.Cantidad,' ', P.Unidad_Medida) as Nombre,P.Nombre_Comercial,
			P.Laboratorio_Comercial,
			P.Laboratorio_Generico,
			P.Id_Producto,PD.Codigo_Cum,
			P.Codigo_Cum as Cum,
			PD.Cantidad as Cantidad_Formulada, 
			PD.NoPrescripcion as Numero_Prescripcion, 
			PD.Tipo_Tecnologia,
			PD.CodSerTecAEntregar

	FROM Dispensacion_Mipres D
	INNER JOIN Producto_Dispensacion_Mipres PD ON D.Id_Dispensacion_Mipres=PD.Id_Dispensacion_Mipres
	INNER JOIN Producto P ON PD.Id_Producto = P.Id_Producto 
	WHERE PD.Id_Dispensacion_Mipres=$idDireccionamiento[Id_Dispensacion_Mipres] 
	AND (D.Estado='Pendiente' OR D.Estado='Programado')";
	return $query;
	
	 /*AND NOT EXISTS(SELECT Id_Dispensacion 
	FROM Dispensacion WHERE Estado_Dispensacion != 'Anulada' 
	AND Id_Tipo_Servicio != 7 AND Id_Dispensacion_Mipres =  D.Id_Dispensacion_Mipres) */

}


function GetAsociados($tipo,$cum,$data){

	$query="SELECT P.Nombre_Comercial,
	            	CONCAT(P.Principio_Activo,' ',P.Presentacion,' ',P.Concentracion,' ', P.Cantidad,' ', P.Unidad_Medida) as Nombre,	
					P.Codigo_Cum, P.Id_Producto, '$data[Cantidad]' as Cantidad, 0 as Seleccionado 
	FROM Producto_Tipo_Tecnologia_Mipres PD 
	INNER JOIN Tipo_Tecnologia_Mipres M ON PD.Id_Tipo_Tecnologia_Mipres=M.Id_Tipo_Tecnologia_Mipres INNER JOIN Producto P ON PD.Id_Producto = P.Id_Producto WHERE (Codigo_Actual='".str_pad((INT)$cum, 2, "0", STR_PAD_LEFT)."' OR Codigo_Anterior='".str_pad((INT)$cum, 2, "0", STR_PAD_LEFT)."') AND M.Codigo='$tipo'";

	$oCon = new consulta();
	$oCon->setQuery($query);
	$oCon->setTipo('Multiple');
	$prod = $oCon->getData();
	unset($oCon);

	return $prod;
}

function GetQueryId(){

	global $idPaciente,$num, $idDireccionamiento;
	$query=" SELECT D.Id_Dispensacion_Mipres
				FROM Dispensacion_Mipres D
				INNER JOIN Producto_Dispensacion_Mipres PD ON D.Id_Dispensacion_Mipres=PD.Id_Dispensacion_Mipres
				INNER JOIN Producto P ON PD.Id_Producto = P.Id_Producto 
				WHERE Id_Paciente='$idPaciente' AND PD.IDDireccionamiento='$num'  
								                AND (D.Estado='Pendiente' OR D.Estado='Programado') ";
	// echo $query;
	return $query;

/* AND
NOT EXISTS(SELECT Id_Dispensacion FROM Dispensacion WHERE Estado_Dispensacion != 'Anulada' AND Id_Tipo_Servicio != 7 
AND Id_Dispensacion_Mipres =  D.Id_Dispensacion_Mipres) */

}








?>