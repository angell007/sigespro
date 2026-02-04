<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once '../../config/start.inc.php';
include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';

$code = $_REQUEST['code'] ? $_REQUEST['code'] : '';


$query = "SELECT *, if(PS.Codigo_Segmento between 70 and 79, 'Servicio', 'Producto') as Tipo FROM Producto_Servicio PS
             WHERE PS.Codigo_Producto like '$code%'    Limit 1  ";
$oCon = new consulta();
$oCon->setQuery($query);
$datos = $oCon->getData();

echo json_encode($datos);
