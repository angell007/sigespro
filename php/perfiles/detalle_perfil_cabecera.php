<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id_perfil = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = 'SELECT P.*
FROM Perfil P
WHERE P.Id_Perfil='.$id_perfil;
$oCon= new consulta();
$oCon->setQuery($query);
$productos = $oCon->getData();
unset($oCon);

echo json_encode($productos);

?>