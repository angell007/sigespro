<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );
$estado = ( isset( $_REQUEST['estado'] ) ? $_REQUEST['estado'] : '' );
$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

// echo $estado;

if($mod=="Novedad"){
 $oItem = new complex($mod,"Id_".$mod,$id);
 $oItem->delete();
 unset($oItem);
}else{

if($estado){
	$oItem = new complex($mod,"Id_".$mod,$id);	
	$oItem->Estado = $estado;
	$oItem->save();
	unset($oItem);
}else{
	$oItem = new complex($mod,"Id_".$mod,$id);
	$oItem->delete();
	unset($oItem);
// 	echo json_encode("($mod,Id_$mod,$id)"); exit;
}

$mensaje['title'] ="Cambio de estado";
$mensaje['message']="Se cambio el estado satisfactoriamente";
$mensaje['type']="success";

}

echo json_encode($mensaje);
// echo json_encode(['message'=>'Guardado exitosamente','type'=>'success',
// 'title'=>'Dirección guardada exitosamente']);


// $oItem = new complex($mod,"Id_".$mod,$id);
// $oItem->delete();
// unset($oItem);

// $oLista = new lista($mod);
// $lista= $oLista->getlist();
// unset($oLista);

// foreach ($lista as $key => $value) {
//     $value = array_map('utf8_encode', $value);
//     $lista[$key] = $value;
//  }



// echo json_encode($lista);
?>