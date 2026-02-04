<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : '';


$oLista = new lista("Municipio");
$oLista->setRestrict("Id_Departamento", "=", $id);
$oLista->setOrder("Nombre", "ASC");
$municipios = $oLista->getlist();
unset($oLista);

echo json_encode($municipios);
