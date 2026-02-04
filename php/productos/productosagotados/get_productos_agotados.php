<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json; charset=UTF-8');

date_default_timezone_set('America/Bogota');

require_once '../../../config/start.inc.php';
include_once '../../../class/class.consulta.php';


$tam = (int)(isset($_REQUEST['tam']) ? $_REQUEST['tam'] : '20');
$pag = (int)(isset($_REQUEST['page']) ? $_REQUEST['page'] : '1');
$limit = ($pag - 1) * $tam;
$having='';
$condiciones = setCondiciones();
$query = getQueryProductos($condiciones);
$oCon = new consulta(); 
$oCon->setQuery("$query Limit $limit, $tam"); 
$oCon->setTipo('Multiple'); 
$respuesta['data']=$oCon->getData();
unset($oCon);
$oCon = new consulta(); 
$oCon->setQuery("SELECT COUNT(*) AS TOTAL FROM($query) P "); 
$respuesta['total']=$oCon->getData()['TOTAL'];

echo json_encode($respuesta);

function getQueryProductos($condiciones)
{
	global $having;

	$query = "SELECT PA.* , 
		CONCAT_WS(' ', P.Principio_Activo, P.Presentacion,P.Concentracion, P.Cantidad, P.Unidad_Medida) AS Nombre, 
		P.Nombre_Comercial, 
		P.Laboratorio_Comercial,
		P.Invima,  P.Estado

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