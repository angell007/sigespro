<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
$datos=(isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '');
$datos = (array) json_decode($datos , true);

		
$oItem = new complex('Contrato_Funcionario','Id_Contrato_Funcionario');
foreach($datos as $index=>$value) {
    $oItem->$index=$value;
}
$oItem->save();
unset($oItem);


$oItem = new complex('Funcionario','Identificacion_Funcionario',$datos['Identificacion_Funcionario'] );
$oItem->Liquidado="No";
$oItem->save();
unset($oItem);


$resultado['mensaje'] = "Se ha agregado correctamente el contrato del funcionario ";
$resultado['tipo'] = "success";

echo json_encode($resultado);

?>