<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$diasAnulacion = isset($_REQUEST['Dias_Anulacion']) ? $_REQUEST['Dias_Anulacion'] : false;
$funcionarioAnulacion = isset($_REQUEST['Funcionario_Anulacion']) ? $_REQUEST['Funcionario_Anulacion'] : false;

if ($diasAnulacion || $diasAnulacion!='undefined' || $funcionarioAnulacion || $funcionarioAnulacion!='undefined'  ) {
    # code...
    $oItem = new complex('Configuracion','Id_Configuracion',1);
    $oItem->Dias_Anulacion_Orden_Compra = $diasAnulacion;
    $oItem->Responsable_Anulacion_Orden_Compra = $funcionarioAnulacion;
    $oItem->save();
    echo json_encode(['type'=>'success', 'title'=>'Operación Exitosa', 'message'=>'Actualizado Satisfactoriamente']) ;

}else{

    echo json_encode(['type'=>'error', 'title'=>'¡Ha ocurrido un error!', 'message'=>'Ocurrió un error, se debe anexar los dias']) ;

}


?>