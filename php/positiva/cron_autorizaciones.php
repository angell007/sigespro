<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');
ini_set("memory_limit", "32000M");
ini_set('max_execution_time', 0);
date_default_timezone_set("America/Bogota");

include_once '/home/sigesproph/public_html/config/config.inc.php';

$MY_ROOT = '/home/sigesproph/public_html/';
// 	exit;

include_once $MY_ROOT.'/class/class.lista.php';
include_once $MY_ROOT.'/class/class.complex.php';
include_once $MY_ROOT.'/class/class.consulta.php';
include_once $MY_ROOT.'/class/class.integracion_positiva.php';
include_once $MY_ROOT.'/config/config.inc.php';


$autorizaciones = [];
$row = [];


actualizarNoProcesadas();



function getQuery()
{
	
	
	$fecha = date('Y-m-d H:i:s', strtotime('-1 day'));
	$fecha = date('2023-01-11 00:00:00');
	$query = "SELECT PD.numeroAutorizacion as Autorizacion, 
	PD.tieneTutela as Tutela, 
	PD.RLmarcaEmpleador as Platino, 
	PD.Pdomicilio as Domicilio, 
	PD.Id_Dispensacion,
	PD.Detalle_Estado 
	From Positiva_Data PD
	Where PD.numeroAutorizacion NOT in (
			SELECT EP.Numero_Autorizacion from Envio_Evento_Positiva EP
			Where EP.Exito = '200'
		)
	And DATE(PD.fechaHoraAutorizacion) >= '$fecha' limit 99";
	return $query;
}

function actualizarNoProcesadas(){
	$query = "SELECT * 
	
		FROM Envio_Evento_Positiva EP
		Where EP.Respuesta like '%no puede ser null%'
		Or EP.Exito = 401
		Or EP.Exito =500 
		OR EP.Respuesta LIKE '%Unsupported response%'
		or EP.Respuesta like '%faltan las credenciales%'
		order By EP.Id_Envio_Evento_Positiva
		
		 ";

	$oCon = new consulta(); 
	$oCon->setQuery($query); 
	$oCon->setTipo('Multiple');
	$resultado = $oCon->getData();
	
	foreach($resultado as $autorizacion){

		
		$pos= new Fase2($autorizacion['Id_Envio_Evento_Positiva'], (array()), 0, '', '', ''); 
		$pos->reProcesarEnvio();
	}




}