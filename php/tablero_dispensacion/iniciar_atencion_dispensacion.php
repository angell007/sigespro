<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.querybasedatos.php');
$queryObj = new QueryBaseDatos();

$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );

$modelo = (array) json_decode(utf8_decode($modelo));

$query='SELECT TA.*, IFNULL(A.Id_Paciente, T.Identificacion_Persona) as Id_Paciente,IFNULL(A.Archivo, "") as Archivo,IFNULL(A.Id_Servicio,"") as Id_Servicio,IFNULL(A.Id_Tipo_Servicio,"") as Id_Tipo_Servicio,"Normal" as Tipo_Turnero 
FROM Turno_Activo TA 
LEFT JOIN Auditoria A ON TA.Id_Auditoria=A.Id_Auditoria
INNER JOIN Turnero T ON TA.Id_Turnero=T.Id_Turnero
WHERE TA.Id_Turno_Activo= '.$modelo['Id_Turno_Activo'];

$queryObj->SetQuery($query);
$turno = $queryObj->ExecuteQuery('simple');


$oItem = new complex("Turnero","Id_Turnero",$modelo['Id_Turnero']);
$oItem->Estado="Atencion";
$oItem->Hora_Inicio_Atencion=date("H:i:s"); 
$oItem->Caja=$modelo['Caja']!='' ? $modelo['Caja'] : '1' ; 
$oItem->save();
$aten = $oItem->getData();
unset($oItem);

$turno['Producto']=GetProducto($turno['Id_Dispensacion_Mipres'],$modelo['Punto_Dispensacion']);


echo json_encode($turno);

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