<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
ini_set("memory_limit", "32000M");
ini_set('max_execution_time', 0);

include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.integracion_positiva.php');
include_once('../../helper/response.php');

$autorizaciones = isset($_REQUEST['autorizaciones']) ? $_REQUEST['autorizaciones'] : '';
$observacion = isset($_REQUEST['observacion']) ? $_REQUEST['observacion'] : '';
$funcionario = isset($_REQUEST['funcionario']) ? $_REQUEST['funcionario'] : '';
$causal = isset($_REQUEST['causal']) ? $_REQUEST['causal'] : '';
$autorizaciones = explode(',', $autorizaciones);
$autorizaciones = "'" . implode("','", $autorizaciones) . "'";
$files = isset($_FILES['files']) ? $_FILES : [];

$query = getQuery($autorizaciones);
$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$datos = $oCon->getData();

// echo json_encode( $datos); exit;

$autorizaciones_correcto = [];
$autorizaciones_error = [];
foreach ($datos as $dispensado) {
	$positiva = new Fase2($dispensado['Autorizacion'], $files, 0, $causal, $observacion, 'OT', $funcionario);
	$respuesta[$dispensado['Autorizacion']] = $positiva->Enviar();
	if ($respuesta[$dispensado['Autorizacion']]['success']){
		array_push($autorizaciones_correcto, $dispensado['Autorizacion']);
	}else{
		array_push($autorizaciones_error, $dispensado['Autorizacion']);
	}
}
$mensaje='';
$tipo="success";
if(count($autorizaciones_correcto)>0){
	$mensaje.='Se ha actualizado el estado de las autorizaciones: ' . implode(', ', $autorizaciones_correcto);
}
if(count($autorizaciones_error)>0){
	$tipo ="warning";
	$mensaje.="\n No se pudo procesar las autorizaciones: " . implode(', ', $autorizaciones_error). '';
}
$respuesta['type'] = $tipo;
$respuesta['title'] = 'Eviado';
$respuesta['message'] = $mensaje ;
echo json_encode($respuesta);


function getQuery($autorizaciones)
{
	$query = "SELECT PD.numeroAutorizacion as Autorizacion, 
	PD.RLnumeroSolicitudSiniestro as Solicitud
	from Positiva_Data PD
	Where PD.numeroAutorizacion in ($autorizaciones)";
	return $query;
}

