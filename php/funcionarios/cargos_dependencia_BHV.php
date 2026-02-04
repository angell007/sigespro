<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

// require_once('../../config/start.inc.php');
// include_once('../../class/class.lista.php');
// include_once('../../class/class.complex.php');
include_once('../../class/class.consulta_paginada.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = " SELECT Id_Cargo,Nombre FROM Cargo WHERE Id_Dependencia = $id ";
$oCon  = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$Cargos = $oCon->getData();
unset($oCon);

echo json_encode($Cargos);

?>
