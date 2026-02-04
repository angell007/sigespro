<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.complex.php');
include_once('../../class/class.http_response.php');




$id_paciente = isset( $_REQUEST['Id_Paciente'] ) ? $_REQUEST['Id_Paciente'] : '';
$correo = isset( $_REQUEST['Correo'] ) ? $_REQUEST['Correo'] : '';
if ($correo) {
    
    $oItem = new complex('Paciente','Id_Paciente',$id_paciente , 'varchar');
    $oItem->Correo = $correo;
    $oItem->save();
    
    echo json_encode(['message'=>'Datos actulizados exitosamente ','type'=>'success',
    'title'=>'Operación exitosa']);
    
}else {
    echo json_encode(['message'=>'Error','type'=>'error',
    'title'=>'Se necisa enviar todos los datos ']);
}
