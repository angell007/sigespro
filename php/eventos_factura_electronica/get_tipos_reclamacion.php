<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once '../../class/class.consulta.php';
include_once '../../class/class.complex.php';
require_once '../../config/start.inc.php';
include_once '../../class/class.lista.php';


$query = "SELECT Concat(Id_Tipo_Reclamacion, ':', Concepto) as Tipo from Tipo_Reclamacion ";
$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');

$reclamaciones['datos']= $oCon->getData();
if (count($reclamaciones['datos'])) {
	$reclamaciones['tipo'] = 'success';
	$reclamaciones['message'] = 'Se ha encontrado datos';
} else {
	$reclamaciones['tipo'] = 'warning';
	$reclamaciones['message'] = 'ha ocurrido un error';
}

echo json_encode($reclamaciones);
