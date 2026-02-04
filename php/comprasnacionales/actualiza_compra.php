<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );
$estado = ( isset( $_REQUEST['estado'] ) ? $_REQUEST['estado'] : '' );
$motivo = ( isset( $_REQUEST['motivo'] ) ? $_REQUEST['motivo'] : '' );

$oItem = new complex('Orden_Compra_Nacional','Id_Orden_Compra_Nacional', $id);

if ($estado=='Anulada') {
    # code...
    $oItem->Estado = $estado;
}else{
    $oItem->Aprobacion = $estado;
    
}
$oItem->save();
unset($oItem);
   

$cont=''; 
if($estado=="Aprobada"){
    $estado2="Aprobacion";
    $cont=' con la observacion: '.$motivo;
}else{
    $estado2=$estado;
    $cont=' con el siguiente motivo: '.$motivo;
} 

$oItem = new complex('Actividad_Orden_Compra',"Id_Acta_Recepcion_Compra");
$oItem->Id_Orden_Compra_Nacional=$id;
$oItem->Identificacion_Funcionario=$funcionario;
$oItem->Detalles="La Orden de Compra ha sido ".$estado.$cont;
$oItem->Fecha=date("Y-m-d H:i:s");
$oItem->Estado =$estado2;
$oItem->save();
unset($oItem);

$resultado['mensaje'] = "Esta Orden de Compra Ha sido ".$estado.$cont;
$resultado['tipo'] = "success";
$resultado['titulo'] = "Operacion Exitosa";

echo json_encode($resultado);        

?>