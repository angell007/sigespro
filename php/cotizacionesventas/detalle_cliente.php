<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$Cliente = ( isset( $_REQUEST['NombreCliente'] ) ? $_REQUEST['NombreCliente'] : '' );

$query = 'SELECT * FROM Cliente WHERE Nombre = "'.$Cliente.'"' ;

$oCon= new consulta();
$oCon->setQuery($query);
$lista = $oCon->getData();
$oCon->setTipo('Multiple');
unset($oCon);

echo json_encode($lista);

?>