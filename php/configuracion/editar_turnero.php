<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
// include_once('../../class/class.lista.php');
// include_once('../../class/class.complex.php');
// include_once('../../class/class.consulta.php');
include_once('../../class/class.querybasedatos.php');

$queryObj = new QueryBaseDatos();

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$puntos = ( isset( $_REQUEST['puntos'] ) ? $_REQUEST['puntos'] : '' );
$servicios = ( isset( $_REQUEST['servicios'] ) ? $_REQUEST['servicios'] : '' );

$datos = (array) json_decode($datos);
$puntos=(array) json_decode($puntos, true );
$servicios=(array) json_decode($servicios);

$oItem = new complex("Turneros","Id_Turneros", $datos['Id_Turneros']);
$oItem->Nombre=strtoupper($datos["Nombre"]);
$oItem->Direccion=strtoupper($datos["Direccion"]);
$oItem->Capita=$datos["Capita"];
$oItem->No_Pos=$datos["No_Pos"];
$oItem->Autorizacion_Servicios=$datos["Autorizacion_Servicios"];
$oItem->Maximo_Turnos=$datos["Maximo_Turnos"];
$oItem->save();
$id_turnero = $oItem->getId();
unset($oItem);

$oCon = new consulta();
$oCon->setQuery("DELETE FROM Punto_Turnero WHERE Id_Turneros = $datos[Id_Turneros]");
$oCon->deleteData();
unset($oCon);

foreach ($puntos as  $value) {
    $oItem = new complex("Punto_Turnero","Id_Punto_Turnero");
    $oItem->Id_Punto_Dispensacion=$value;
    $oItem->Id_Turneros=$id_turnero;
    $oItem->Capita=$datos["Capita"];
    $oItem->No_Pos=$datos["No_Pos"];
    $oItem->save();
    unset($oItem);

}

GuardarServiciosTurnero($servicios, $datos['Id_Turneros']);

$resultado['mensaje']="Turnero editado Correctamente";
$resultado['tipo']="success";

echo json_encode($resultado);

function GuardarServiciosTurnero($servicios, $idTurnero){
	global $queryObj;

	$query_delete = 'DELETE FROM Servicio_Turnero WHERE Id_Turnero = '.$idTurnero;
	$queryObj->SetQuery($query_delete);
	$queryObj->QueryUpdate();

	foreach ($servicios as $service) {
		$oItem= new complex("Servicio_Turnero","Id_Servicio_Turnero");
	    $oItem->Id_Turnero =$idTurnero;
	    $oItem->Id_Servicio =$service;
    	$oItem->save();
	    unset($oItem);
	}
}
?>