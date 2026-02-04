<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.complex.php');
include_once('../../class/class.http_response.php');



$tel_rec = isset( $_REQUEST['Telefono_Paciente'] ) ? $_REQUEST['Telefono_Paciente'] : '';
$tel_rec = json_decode($tel_rec,true);
if ($tel_rec) {
    
  $oItem = new complex('Paciente_Telefono','Id_Paciente_Telefono',
			      $tel_rec['Id_Telefono_Paciente'] );
    $oItem->Id_Paciente = $tel_rec['Id_Paciente'];
    $oItem->Numero_Telefono = $tel_rec['Numero_Telefono'];
    $oItem->save();
    
    echo json_encode(['message'=>'Datos actulizados exitosamente ','type'=>'success',
    'title'=>'Operación exitosa']);
    
}else {
    echo json_encode(['message'=>'Error','type'=>'error',
    'title'=>'Se necisa enviar todos los datos ']);
}
