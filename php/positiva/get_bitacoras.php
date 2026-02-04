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
$oCon->setQuery( "$query Limit $limit, $tamPag"); 
$oCon->setTipo('Multiple');
$eventos['Datos'] = $oCon->getData();
foreach ($eventos['Datos'] as $key => $value) {
	$value['Respuesta'] = json_decode($value['Respuesta'], true); 
	$eventos['Datos'][$key]=$value;
}
$eventos['Desde']= $limit+1;
$eventos['Hasta']= $limit+1+$tamPag; 

$oCon = new consulta();
$oCon->setQuery( "SELECT Count(D.Id_Envio_Evento_Positiva) as Total from ($query)D"); 
$total= $oCon->getData()['Total']; 

$eventos['numReg']= $total; 


echo json_encode($eventos);


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
	EP.Id_Envio_Evento_Positiva,
	EP.Cantidad_Entregada as Cantidad,
	EP.Observacion,
	EP.Fecha_Envio,
	EP.Codigo_Causal_Positiva,
	C.Descripcion as Causal,
	EP.Json_Dispensacion,
	EP.Respuesta,
	CONCAT_WS(' ', F.Nombres, F.Apellidos) as Funcionario,
	PD.numeroAutorizacion as Autorizacion, 
	PD.RLnumeroSolicitudSiniestro as Solicitud, 
	if(EP.Exito =200, 'Si', 'No') as Enviada, 
	D.Codigo as Dispensacion, 
	D.Id_Dispensacion

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