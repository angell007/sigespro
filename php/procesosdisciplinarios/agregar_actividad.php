<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

date_default_timezone_set('America/Bogota');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once('../../class/class.configuracion.php');
include_once('../../class/class.consulta.php');
require_once('../../class/class.qr.php');  /* AGREGAR ESTA CLASE PARA GENERAR QR */

$Proceso = ( isset( $_REQUEST['Proceso'] ) ? $_REQUEST['Proceso'] : false );
$resultado = [];

if ($Proceso) {
    $Proceso = json_decode($Proceso,true);

    if($Proceso['Id_Proceso_Disciplinario']){
        $oItem = new complex('Proceso_Disciplinario','Id_Proceso_Disciplinario', $Proceso['Id_Proceso_Disciplinario']);
    }else{
        $oItem = new complex('Proceso_Disciplinario','Id_Proceso_Disciplinario');
    }
    //$oItem->Fecha                      = $Proceso['Fecha'];
    $oItem->Identificacion_Funcionario = $Proceso['Funcionario']['Identificacion_Funcionario'];
    $oItem->Fecha_Inicio               = $Proceso['Fecha_Inicio'];
    $oItem->Fecha_Fin                  = $Proceso['Fecha_Fin'];
    $oItem->Funcionario_Reporta        = $Proceso['Funcionario_Reporta'];
    $oItem->Descripcion_Proceso        = $Proceso['Descripcion_Proceso'];

    $oItem->save();
    unset($oItem); 

    // $oItem = new complex('Actividad_Proceso_Disciplinario','Id_Proceso_Disciplinario');
    // $oItem->Id_Proceso_Disciplinario = $Proceso['Id_Proceso_Disciplinario'];
    // $oItem->Identificacion_Funcionario = $data['Identificacion_Funcionario'];
    // $oItem->Funcionario_Reporta = $Proceso['Funcionario'];
    // $oItem->Actividad = $Proceso['Detalles'];
    // $oItem->Id_Proceso_Disciplinario = $Proceso['Id_Proceso_Disciplinario'];
    // $oItem->save();
    // unset($oItem);

    $resultado['codigo'] = "success";
    $resultado['titulo'] = "Exito!";
    $resultado['mensaje'] = "Se ha agregado la proceso Disciplinario exitosamente.";
} else {
    $resultado['codigo'] = "error";
    $resultado['titulo'] = "Oops!";
    $resultado['mensaje'] = "Ha ocurrido un error inesperado.";
}

echo json_encode($resultado);
?> 