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

$id= ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$tipo = ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : '' );
$caja = ( isset( $_REQUEST['caja'] ) ? $_REQUEST['caja'] : '' );
$tipo = ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : '' );
$punto = ( isset( $_REQUEST['punto'] ) ? $_REQUEST['punto'] : '' );
   
$query= GetQuery();


$queryObj->SetQuery($query);
$turno = $queryObj->ExecuteQuery('simple');

if($turno){
	$turno['Producto']=GetProducto($turno['Id_Dispensacion_Mipres'],$punto);
}

if($turno){
	$oItem = new complex("Turnero","Id_Turnero",$turno["Id_Turnero"]);
	$oItem->Estado="Atencion";
	$oItem->Caja=$caja!='' ? $caja : '1';
	$oItem->Orden = 0;
	$oItem->save();
	$aten = $oItem->getData();
	unset($oItem);
}




echo json_encode($turno);

function GetQuery(){
	global $id, $tipo;

	$query='SELECT TA.*, IFNULL(A.Id_Paciente, T.Identificacion_Persona) as Id_Paciente,IFNULL(A.Archivo, "") as Archivo,IFNULL(A.Id_Servicio,"") as Id_Servicio,IFNULL(A.Id_Tipo_Servicio,"") as Id_Tipo_Servicio,IFNULL(A.Punto_Pre_Auditoria,0) as Id_Punto_Dispensacion 
	FROM Turno_Activo TA 
	LEFT JOIN Auditoria A ON TA.Id_Auditoria=A.Id_Auditoria
	INNER JOIN Turnero T ON TA.Id_Turnero=T.Id_Turnero
	WHERE TA.Id_Turneros= '.$id.' AND TA.Estado="'.$tipo.'"  Order BY Hora_Turno ASC, Id_Turno_Activo ASC Limit 1  ';

	return $query;
}

function GetProducto($id_mipres,$id_punto){
	global $queryObj;

	$producto=[];
	if($id_mipres!='0'){
		$query="SELECT PD.Id_Dispensacion_Mipres, CONCAT(P.Principio_Activo,' ',P.Presentacion,' ',P.Concentracion,' ', P.Cantidad,' ', P.Unidad_Medida) as Nombre,P.Nombre_Comercial,
		P.Laboratorio_Comercial,
		P.Laboratorio_Generico,
		P.Id_Producto,PD.Codigo_Cum,
		P.Codigo_Cum as Cum,PD.Cantidad as Cantidad_Formulada, PD.NoPrescripcion as Numero_Prescripcion 
		FROM Producto_Dispensacion_Mipres PD 
		INNER JOIN Producto P ON PD.Id_Producto=P.Id_Producto 
		WHERE PD.Id_Dispensacion_Mipres= ".$id_mipres;

		$queryObj->SetQuery($query);
		$producto = $queryObj->ExecuteQuery('Multiple');

		
		
	}


	return $producto;
	
}




?>