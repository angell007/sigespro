<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

date_default_timezone_set("America/Bogota");
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.integracion_positiva.php');
include_once('../../helper/response.php');

$autorizacion= (isset($_REQUEST['autorizacion'])?$_REQUEST['autorizacion']: '');
$query = getQuery($autorizacion); 
$oCon = new consulta();
$oCon->setQuery($query); 
$oCon->setTipo('Multiple');
$eventos = $oCon->getData();
foreach ($eventos as $key => $value) {
	$value['Respuesta'] = json_decode($value['Respuesta'], true); 
	$eventos[$key]=$value;
}



echo json_encode($eventos);


function getQuery($autorizacion)
{
	$query = "SELECT
	EP.Cantidad_Entregada as Cantidad,
	EP.Observacion,
	EP.Codigo_Causal_Positiva,
	C.Descripcion as Causal,
	EP.Respuesta,
	EP.Fecha_Envio,
	 PD.numeroAutorizacion as Autorizacion
	From Positiva_Data PD
	inner Join Envio_Evento_Positiva EP on EP.Numero_Autorizacion = PD.numeroAutorizacion
	Inner Join Causal_Positiva C on C.Codigo = EP.Codigo_Causal_Positiva
	AND EP.Exito = 200
	Where PD.numeroAutorizacion like '$autorizacion' ";

	return $query;
}