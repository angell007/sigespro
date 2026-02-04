<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

date_default_timezone_set("America/Bogota");
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
// include_once('../../class/class.integracion_positiva.php');
include_once('../../helper/response.php');

$tamPag = 20;
$limit = 0; 

if (!isset($_REQUEST['page']) || $_REQUEST['page'] == '') { 
    $paginaAct = 1; 
} else { 
    $paginaAct = $_REQUEST['page']; 
} 
$limit = ($paginaAct-1) * $tamPag; 


$query = getQuery(); 
$oCon = new consulta();
$oCon->setQuery( "$query"); 
$oCon->setTipo('Multiple');
$eventos['Datos'] = $oCon->getData();
foreach ($eventos['Datos'] as $key => $value) {
	$value['Respuesta'] = json_decode($value['Respuesta'], true); 
	$eventos['Datos'][$key]=$value;
}



echo json_encode($eventos['Datos']);


function getQuery()
{

	$condiciones = array();
	array_push($condiciones, "1"); 
	foreach ($_REQUEST as $key => $value) {
		if($value && !in_array($key, ['Fecahs', 'page'])){
			$condicion = " $key like '%$value%'";
			array_push($condiciones, $condicion); 
		}
	}
	$condiciones = "HAVING " . implode(" and ", $condiciones);


	$query = "SELECT
	PD.RLnumeroSolicitudSiniestro as Solicitud, 
	PD.numeroAutorizacion as Autorizacion, 
	D.Codigo as Dispensacion, 
	EP.Cantidad_Entregada as Cantidad,
	EP.Observacion,
	CONCAT_WS(' ', F.Nombres, F.Apellidos) as Funcionario,
	EP.Fecha_Envio,
	C.Descripcion as Causal,
	-- item.Respuesta? item.Respuesta.data? item.Respuesta.data.resultSolicitud : item.Respuesta.eror? item.Respuesta.error : item.Respuesta.message : ''
	if( EP.Respuesta!= '', 
		if(JSON_EXTRACT(EP.Respuesta, '$.data') != '', 
			ifnull(JSON_EXTRACT(EP.Respuesta, '$.data.resultSolicitud' ), JSON_EXTRACT(EP.Respuesta, '$.data.message' )), 
			if(JSON_EXTRACT(EP.Respuesta, '$.error') != '', 
				JSON_EXTRACT(EP.Respuesta, '$.data.message' ), '')
		)
	, ''
	) As Respuesta,
	
	if(EP.Exito =200, 'Si', 'No') as Enviada

	From  Envio_Evento_Positiva EP 
	Inner Join Causal_Positiva C on C.Codigo = EP.Codigo_Causal_Positiva
	inner Join Positiva_Data PD on EP.Numero_Autorizacion = PD.numeroAutorizacion
	Inner Join Funcionario F on F.Identificacion_Funcionario = EP.Identificacion_Funcionario

	LEFT JOIN Dispensacion D on D.Id_Dispensacion = PD.Id_Dispensacion
	$condiciones
	Order by EP.Id_Envio_Evento_Positiva desc
	
	";

	return $query;
}