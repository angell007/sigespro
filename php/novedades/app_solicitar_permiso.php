<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once('../../class/class.configuracion.php');

$inicio = ( isset( $_REQUEST['inicio'] ) ? $_REQUEST['inicio'] : '' );
$fin = ( isset( $_REQUEST['fin'] ) ? $_REQUEST['fin'] : '' );
$observaciones = ( isset( $_REQUEST['observaciones'] ) ? $_REQUEST['observaciones'] : '' );
$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );


$oItem = new complex('Novedad',"Id_Novedad");
$oItem->Id_Tipo_Novedad = 17;
$oItem->Fecha_Inicio = $inicio;
$oItem->Fecha_Fin = $fin;
$oItem->Funcionario_Reporta = $funcionario;
$oItem->Observaciones = $observaciones;
$oItem->Identificacion_Funcionario = $funcionario;
$oItem->Estado = "Pendiente";

$oItem->save();
$id_novedad = $oItem->getId();
unset($oItem);

if($id_novedad != ""){
    $resultado['mensaje'] = "Se ha solicitado Correctamente el Permiso";
    $resultado['tipo'] = "success";
}else{
    $resultado['mensaje'] = "ha ocurrido un error guardando la informacion, por favor verifique";
    $resultado['tipo'] = "error";
}

echo json_encode($resultado);



?>