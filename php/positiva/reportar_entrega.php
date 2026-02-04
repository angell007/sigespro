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

$datos = isset($_REQUEST['datos']) ? $_REQUEST['datos'] : '';

$datos = json_decode($datos, true);

$autorizaciones = explode(',', $datos['Autorizacion']);
$dispensacion = $datos['Id_Dispensacion'];
$autorizaciones = "'" . implode("','", $autorizaciones) . "'";

$query = getQuery($autorizaciones, $dispensacion);
$oCon = new consulta();
$oCon->setQuery($query);
$entrega = $oCon->getData();
$acta_entrega['files'] = ValidarActaEntrega($datos['Id_Dispensacion']);
$mensaje = '';
try {
	$positiva = new Fase2($datos['Autorizacion'], $acta_entrega, $datos['Cantidad_Entregada'], $datos['Causal'], 'Entrega exitosa', 'SE', $datos['Identificacion_Funcionario'], $datos['Fecha_Dispensacion']);
	$rPositiva =  $positiva->Enviar();
	$respuesta[$datos['Autorizacion']] = $rPositiva;


	$tipo = $rPositiva['success'] ? "success" : 'error';
	$respuesta['title'] = $rPositiva['success'] ? 'Enviado' : 'No Enviado';
	$mensaje = $rPositiva['success'] ? $rPositiva['message'] : $rPositiva['data']['resultSolicitud'];
	//code...
} catch (\Throwable $th) {


	$tipo = "error";
	$respuesta['title'] = 'Error de comunicacion con positiva';
	$mensaje='Error en la comunicacion';
}




$respuesta['type'] = $tipo;

$respuesta['message'] = $mensaje;
echo json_encode($respuesta);


function getQuery($autorizaciones, $dis)
{
	$query = "SELECT PD.numeroAutorizacion as Autorizacion, 
	PD.RLnumeroSolicitudSiniestro as Solicitud
	from Positiva_Data PD
	Inner Join Dispensacion D 
	Where PD.numeroAutorizacion in ($autorizaciones)
	And D.Id_Dispensacion = $dis
	";
	return $query;
}

function ValidarActaEntrega($id_dis)
{
	$oItem = new complex('Dispensacion', 'Id_Dispensacion', $id_dis);
	$dis = $oItem->getData();

	if ($dis) {
		$ruta = $dis['Acta_Entrega'];
		$row['name'] = pathinfo($ruta, PATHINFO_BASENAME);
		$row['type'] = "application/" . pathinfo($ruta, PATHINFO_EXTENSION);
		$row['tmp_name'] = $ruta;
		return $row;
	}
}
