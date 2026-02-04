<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json; charset=UTF-8');

date_default_timezone_set('America/Bogota');

require_once '../../../config/start.inc.php';
include_once '../../../class/class.consulta.php';
$limit = ($pag - 1) * $tam;
$having='';
$condiciones = setCondiciones();
$query = getQueryProductos($condiciones);
$oCon = new consulta(); 
$oCon->setQuery($query); 
$oCon->setTipo('Multiple'); 
$respuesta=$oCon->getData();
unset($oCon);

echo json_encode($respuesta);

function getQueryProductos($condiciones)
{
	global $having;

	$query = "SELECT 
		PA.Codigo_Cum , 
		CONCAT_WS(' ', P.Principio_Activo, P.Presentacion,P.Concentracion, P.Cantidad, P.Unidad_Medida) AS Nombre, 
		P.Nombre_Comercial, 
		P.Laboratorio_Comercial,
		P.Invima, 
		PA.Fecha_Agotado , 
		IFNULL(PA.Fecha_Ingreso , 'Sin registro') as Fecha_Ingreso,
		P.Estado as Estado_Producto

		FROM Producto_Agotado PA 
		inner join Producto P on P.Codigo_Cum = PA.Codigo_Cum	
		WHERE 
		$condiciones
		Group by PA.Codigo_Cum
		$having
		ORDER BY Id_Producto_Agotado desc
		";
	return $query;
}

function setCondiciones(){
	global $having;
	$req = $_REQUEST;

	$condiciones = [];
	array_push($condiciones, "1");
	foreach ($req as $key => $value) {
		if($value && $key!='Nombre' & $key!='page'){
			array_push($condiciones, "P.$key LIKE '%$value%'");
		}
		if($key == 'Nombre' &&$value){
			$having = "HAVING Nombre LIKE '%$value%' " ;
		}
		
	}



	return implode(' AND ', $condiciones);
}