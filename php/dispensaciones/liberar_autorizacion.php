<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once './delete_alerts.php';
require_once '../../config/start.inc.php';
include_once '../../class/class.querybasedatos.php';

$positiva = isset($_REQUEST['positiva']) ? $_REQUEST['positiva'] : null;
$dispensacion = isset($_REQUEST['dispensacion']) ? $_REQUEST['dispensacion'] : null;
$func = isset($_REQUEST['funcionario']) ? $_REQUEST['funcionario'] : null;

$oItem = new complex('Dispensacion', 'Codigo', $dispensacion);
$dis = $oItem->getData();
if ($dis['Estado_Dispensacion'] == 'Anulada') {
	if ($positiva) {
		$query = "UPDATE Positiva_Data SET Id_Dispensacion = NULL  WHERE numeroAutorizacion = '$positiva' and Id_Dispensacion = '$dis[Id_Dispensacion]'" ;
		$oCon = new consulta();
		$oCon->setQuery($query);
		$resultado = $oCon->createData();
		unset($oCon);
	}
	
	$ActividadDis["Identificacion_Funcionario"] = $func ? $func : 12345;
	$ActividadDis["Id_Dispensacion"] = $dis['Id_Dispensacion'];
	$ActividadDis['Fecha'] = date("Y-m-d H:i:s");
	$ActividadDis["Detalle"] = "Autorizacion $positiva liberada de la dispensacion $dis[Codigo]" ;
	$ActividadDis["Estado"] = "Anulada";

	$oItem = new complex("Actividades_Dispensacion", "Id_Actividades_Dispensacion");
	foreach ($ActividadDis as $index => $value) {
		$oItem->$index = $value;
	}
	$oItem->save();
	unset($oItem);

	$resu["Mensaje"] = "Autorizacion $positiva liberada de manera exitosa";
	$resu ['Tipo'] = "success";
	$resu ['Titulo'] = "Correcto!";


} else {

	$resu["Mensaje"] = "No se puede liberar autorizacion debido a que la dis no está anulada, por favor anular la dispensacion para liberar la autorizacion";
	$resu ['Tipo'] = "error";
	$resu ['Titulo'] = "Dispensacion Activa";
}

echo json_encode($resu);
