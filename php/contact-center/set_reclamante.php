<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.complex.php');

$reclamante = isset( $_REQUEST['Reclamante'] ) ? $_REQUEST['Reclamante'] : '';
$reclamante = json_decode($reclamante,true);

$oItem = new complex('Reclamante','Id_Reclamante',$reclamante['Id_Reclamante'] );

unset($reclamante['Id_Reclamante']);
foreach ($reclamante as $key => $value) {
    $oItem->$key = $value;
}

$oItem->save();

echo json_encode(['message'=>'Datos actulizados exitosamente ','type'=>'success',
'title'=>'Operación exitosa']);