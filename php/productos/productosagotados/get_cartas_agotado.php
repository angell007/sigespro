<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

date_default_timezone_set('America/Bogota');

require_once '../../../config/start.inc.php';
include_once '../../../class/class.consulta.php';


$cum = (isset($_REQUEST['Codigo_Cum']) ? $_REQUEST['Codigo_Cum'] : '');
$limit = ($pag - 1) * $tam;

$condiciones = "WHERE PA.Codigo_Cum = '$cum'";
$query = getQueryProductos($condiciones);
$oCon = new consulta(); 

// echo $query; exit;
$oCon->setQuery("$query"); 
$oCon->setTipo('Multiple'); 
$respuesta=$oCon->getData();
unset($oCon);
echo json_encode($respuesta);


function getQueryProductos($condiciones)
{
	

	$query = "SELECT PA.* , 
		CONCAT_WS(' ', P.Nombre_Comercial, P.Presentacion, P.Concentracion, P.Cantidad, P.Unidad_Medida ) as Nombre,
		P.Nombre_Comercial, 
		P.Laboratorio_Comercial,
		P.Invima,  P.Estado

		FROM Producto_Agotado PA 
		inner join Producto P on P.Codigo_Cum = PA.Codigo_Cum	
		$condiciones
		Group By PA.Id_Producto_Agotado
		ORDER BY Id_Producto_Agotado desc
		";
	return $query;
}
