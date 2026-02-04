<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.contabilizar.php');
require('./funciones.php');

$tipo = isset($_REQUEST['Tipo']) && $_REQUEST['Tipo'] == 'Normal' ? null : 'ng-select';

$tipos_documentos = getTiposDocumentos($tipo);

echo json_encode($tipos_documentos);
          
?>