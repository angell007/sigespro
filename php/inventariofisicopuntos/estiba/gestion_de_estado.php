<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$id_inventario_fisico = isset($_REQUEST['Id_Doc_Inventario_Fisico_Punto']) ? $_REQUEST['Id_Doc_Inventario_Fisico_Punto'] : false;
$tipo= isset($_REQUEST['tipo_accion']) ? $_REQUEST['tipo_accion'] : false;

// Cambiar el estado del inventario fisico
$oItem = new complex('Doc_Inventario_Fisico_Punto', 'Id_Doc_Inventario_Fisico_Punto', $id_inventario_fisico);
$oItem->Estado = $tipo;
$oItem->save();
$band = $oItem->getId();
unset($oItem);
if ($tipo!='Haciendo Primer Inventario' || $tipo!='Haciendo Segundo Inventario') {
    
    if($band){
        $resultado['titulo'] = "Operación Exitosa";
        $resultado['mensaje'] = "Se ha guardado correctamente el inventario , puede continuar en cualquier momento";
        $resultado['tipo'] = "success";
    }else{
        $resultado['titulo'] = "Error";
        $resultado['mensaje'] = "Ha ocurrido un error inesperado. Por favor verifique su conexión a internet.";
        $resultado['tipo'] = "error";
    }
    echo json_encode($resultado);
}



?>