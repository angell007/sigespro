<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = 'SELECT M.Nombre AS Ciudad, C.Tipo_Identificacion AS Tipo_ID, C.Nombre AS Cliente, C.Razon_Social AS Razon, C.Tipo AS Tipo, C.Id_Cliente as Id_Cliente FROM `Cliente` C INNER JOIN Municipio M ON C.Ciudad = M.Id_Municipio WHERE Estado = "Activo" ' ;

$oCon= new consulta();
$oCon->setQuery($query);
$vista = $oCon->getData();
unset($oCon);

echo json_encode($vista);