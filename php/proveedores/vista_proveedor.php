<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = 'SELECT M.Nombre AS Ciudad, P.Tipo_Identificacion AS Tipo_ID, P.Nombre AS Proveedor, P.Razon_Social AS Razon, P.Tipo AS Tipo, P.Id_Proveedor AS Id_Proveedor FROM `Proveedor` P INNER JOIN Municipio M ON P.Ciudad = M.Id_Municipio WHERE Estado = "Activo" ' ;

$oCon= new consulta();
$oCon->setQuery($query);
$vista = $oCon->getData();
unset($oCon);

echo json_encode($vista);