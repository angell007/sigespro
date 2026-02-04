<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );
$punto = ( isset( $_REQUEST['punto'] ) ? $_REQUEST['punto'] : '' );

$query = "SELECT Id_Diario_Cajas_Dispensacion FROM `Diario_Cajas_Dispensacion` WHERE Estado != 'Anulado' AND Fecha_Fin=CURRENT_DATE AND Id_Punto_Dispensacion=$punto AND Identificacion_Funcionario=$funcionario";
$oCon= new consulta();
$oCon->setQuery($query);
$caja = $oCon->getData();
unset($oCon);

if ($caja) {
    $resultado['status'] = true;
} else {
    $resultado['status'] = false;
}

echo json_encode($resultado);

?>