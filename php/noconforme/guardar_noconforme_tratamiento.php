<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
require_once('../../class/class.configuracion.php');
$configuracion = new Configuracion();


$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );
$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$productos = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );
$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );


$datos = (array) json_decode($datos);
$productos = (array) json_decode($productos , true);


if(isset($id)&&$id!=""){
    
    $oItem = new complex($mod,"Id_".$mod,$id);
    
}else{
    $oItem = new complex('Configuracion','Id_Configuracion',1);
    $nc = $oItem->getData();
    
    $oItem->No_Conforme=$oItem->No_Conforme+1;
    $oItem->save();
    $num_noconforme=$nc["No_Conforme"];
    unset($oItem);
    
    $cod = "NC".sprintf("%05d", $num_noconforme); 
    
    $cod = $configuracion->Consecutivo('No_Conforme');
    $datos['Codigo']=$cod;
    
    $oItem = new complex($mod,"Id_".$mod);
}

foreach($datos as $index=>$value) {
    $oItem->$index=$value;
}
$oItem->save();
$id_tratamiento = $oItem->getId();
$resultado = array();
unset($oItem);

foreach($productos as $producto){
    $oItem = new complex('Tratamiento_No_Conforme',"Id_Tratamiento_No_Conforme");
    $producto["Id_No_Conforme"]=$id_tratamiento;
    foreach($producto as $index=>$value) {
        $oItem->$index=$value;
    }
    $oItem->save();
    unset($oItem);
}

$oLista = new lista($mod);
$lista= $oLista->getlist();
unset($oLista);

if(json_encode($lista)){
    $resultado['mensaje'] = "Se ha guardado correctamente la cotización con codigo: ". $datos['Codigo'];
    $resultado['tipo'] = "success";
}else{
    $resultado['mensaje'] = "ha ocurrido un error guardando la información, por favor verifique";
    $resultado['tipo'] = "error";
}

echo json_encode($resultado);

?>      
