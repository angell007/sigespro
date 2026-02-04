<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$idListaGanancia = ( isset( $_REQUEST['IdListaGanancia'] ) ? $_REQUEST['IdListaGanancia'] : '' );

$query = 'SELECT Porcentaje FROM Lista_Ganancia WHERE Id_Lista_Ganancia = ' .$idListaGanancia ;
$oCon= new consulta();
$oCon->setQuery($query);
$porcentaje = $oCon->getData();
unset($oCon);

echo json_encode($porcentaje);

?>