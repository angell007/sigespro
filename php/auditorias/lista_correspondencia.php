<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$punto = isset($_REQUEST['punto']) ? $_REQUEST['punto'] : false;

$condicion = '';

if ($punto) {
   $condicion .= 'WHERE Punto_Envio = "' . $punto.'"';
}

$query='SELECT C.*, CONCAT(F.Nombres," ", F.Apellidos) as Funcionario_Envio, F.Imagen
From Correspondencia C
INNER JOIN Funcionario F
ON C.Id_Funcionario_Envia=F.Identificacion_Funcionario '.$condicion;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$correspondencia = $oCon->getData();
unset($oCon);

echo json_encode($correspondencia);
?>