<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$direccion = isset($_REQUEST['direccion']) ? $_REQUEST['direccion'] : '';
$direccion = json_decode($direccion,true);


try{
  if($direccion){
    $oItem = new complex('Paciente_Direccion','Id_Paciente_Direccion',$direccion['Id_Paciente_Direccion']);
    unset($direccion['Municipios']);
    unset($direccion['Selected']);
    unset($direccion['Id_Paciente_Direccion']);
    unset($direccion['Tipo_Direccion']);

    foreach($direccion as $key =>$valor){
      $oItem->$key = $valor;
    }
    $oItem->save();

    echo json_encode(['message'=>'Guardado exitosamente','type'=>'success',
	      'title'=>'Dirección guardada exitosamente']);
  }
  
}catch(Exception $err){
  var_dump($error->getMessage());
}

