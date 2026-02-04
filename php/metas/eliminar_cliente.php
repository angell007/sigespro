<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );

$query ="DELETE FROM Meta_Cliente WHERE Id_Meta_Cliente IN (".$datos.")"; 
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->deleteData();
unset($oCon); 

$resultado["Respuesta"]="ok";

echo json_encode($resultado);

?>