<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require('../contabilidad/funciones.php');

$id = isset($_REQUEST['nit']) ? $_REQUEST['nit'] : false;
$id_plan_cuenta = isset($_REQUEST['id_plan_cuenta']) ? $_REQUEST['id_plan_cuenta'] : false;

$resultado['Facturas'] = listaCartera($id, $id_plan_cuenta);


echo json_encode($resultado);

          
?>