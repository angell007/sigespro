<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );
$punto = ( isset( $_REQUEST['punto'] ) ? $_REQUEST['punto'] : '' );

$datos = (array) json_decode($datos, true);

$oItem = new complex('Gastos_Cajas_Dispensaciones', 'Id_Gastos_Cajas_Dispensaciones');
$oItem->Identificacion_Funcionario = $funcionario;
$oItem->Id_Punto_Dispensacion = $punto;
$gasto = number_format($datos['Gasto'],2,".","");
$oItem->Gasto = $gasto;
$oItem->Motivo = $datos['Motivo'];
$oItem->Observaciones = $datos['Observaciones_Gastos'];
$oItem->save();
$id_gasto = $oItem->getId();
unset($oItem);

if ($id_gasto) {
    $resultado['titulo'] = "Operación Exitosa";
    $resultado['mensaje'] = "Se ha agregado el gasto correctamente.";
    $resultado['tipo'] = "success";
} else {
    $resultado['titulo'] = "Error";
    $resultado['mensaje'] = "Ha ocurrido un error inesperado, por favor vuelva a intentarlo.";
    $resultado['tipo'] = "error";
}

echo json_encode($resultado);

?>