<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$tipo = ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : '' );
$motivo = ( isset( $_REQUEST['motivo'] ) ? $_REQUEST['motivo'] : '' );
$observaciones = ( isset( $_REQUEST['observaciones'] ) ? $_REQUEST['observaciones'] : '' );
$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );

$oItem = new complex("Certificado_Laboral","Id_Certificado_Laboral");
$oItem->Tipo=$tipo;
$oItem->Motivo=$motivo;
$oItem->Observaciones=$observaciones;
$oItem->Identificacion_Funcionario=$funcionario;
$oItem->save();
$id_certi = $oItem->getId();
unset($oItem);


if($id_certi != ""){
    $resultado['mensaje'] = "Se ha generado correctamente el Certificado.";
    $resultado['tipo'] = "success";
}else{
    $resultado['mensaje'] = "ha ocurrido un error guardando la informacion, por favor verifique";
    $resultado['tipo'] = "error";
}

echo json_encode($resultado);
?>