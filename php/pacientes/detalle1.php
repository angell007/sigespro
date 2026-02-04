<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$link = mysql_connect('localhost', 'corvusla_proh', 'Proh2018') or die('No se pudo conectar: ' . mysql_error());
mysql_select_db('corvusla_proh') or die('No se pudo seleccionar la base de datos');

$idPaciente = ( isset( $_REQUEST['IdPaciente'] ) ? $_REQUEST['IdPaciente'] : '' );

$oItem = new complex('Paciente','Id_Paciente',$idPaciente);
$punto = $oItem->getData();

echo json_encode($punto);

?>