<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = 'SELECT * FROM Turneros WHERE Id_Turneros='.$id; 


$oCon= new consulta();
$oCon->setQuery($query);
$turneros= $oCon->getData();
unset($oCon);

$query = 'SELECT Id_Punto_Dispensacion FROM Punto_Turnero WHERE Id_Turneros='.$id; 

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$puntos_turnero= $oCon->getData();
unset($oCon);

$puntos = [];

foreach ($puntos_turnero as $punto) {
    $puntos[] = $punto['Id_Punto_Dispensacion'];
}

$servicios_turnero = GetSeviciosTurnero($id);

$resultado['turneros'] = $turneros;
$resultado['puntos'] = $puntos;
$resultado['servicios'] = $servicios_turnero;

echo json_encode($resultado);

function GetSeviciosTurnero($idTurneros){
	$query = 'SELECT Id_Servicio FROM Servicio_Turnero WHERE Id_Turnero='.$idTurneros;

	$serv = array();

	$oCon= new consulta();
	$oCon->setQuery($query);
	$oCon->setTipo('multiple');
	$servicios_turnero= $oCon->getData();
	unset($oCon);

	foreach ($servicios_turnero as $key => $value) {
		foreach ($value as $id_servicio) {
			array_push($serv, $id_servicio);
		}
	}

	return $serv;
}
?>