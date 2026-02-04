<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

date_default_timezone_set('America/Bogota');

require_once '../../../config/start.inc.php';
include_once '../../../class/class.consulta.php';

$cum = (isset($_REQUEST['Codigo_Cum']) ? $_REQUEST['Codigo_Cum'] : '');


$query = "SELECT P.Codigo_Cum,
			'' as Fecha_Ingreso,
			'' as Fecha_Agotado,
			'' as Archivo,
			CONCAT_WS(' ', P.Nombre_Comercial, P.Presentacion, P.Concentracion, P.Cantidad, P.Unidad_Medida ) as Nombre_Producto
			From Producto P Where P.Codigo_Cum = '$cum'
			";
$oCon = new consulta();
$oCon->setQuery($query);
echo json_encode($oCon->getData());