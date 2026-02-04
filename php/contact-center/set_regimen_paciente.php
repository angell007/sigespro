<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$paciente = isset( $_REQUEST['Paciente'] ) ? $_REQUEST['Paciente'] : '';
$id_dispensacion = isset( $_REQUEST['Id_Dispensacion'] ) ? $_REQUEST['Id_Dispensacion'] : '';
$paciente = json_decode($paciente,true);

$oItem = new complex('Paciente','Id_Paciente',$paciente['Id_Paciente'] , 'varchar' );
$oItem->Id_Regimen = $paciente['Id_Regimen'];
$oItem->save();
unset($oItem);


if ( !$dis['Id_Tipo_Servicio'] ||  $dis['Id_Tipo_Servicio']  == 0) {
    
    $oItemRegimen = new complex('Regimen','Id_Regimen', $paciente['Id_Regimen'] );
    $regimen = $oItemRegimen->getData();
    unset($oItemRegimen);

    $Id_Tipo_Servicio  = $regimen['Nombre'] == 'Subsidiado' ? 5 : 3;
    $query = 'UPDATE Dispensacion Set Id_Tipo_Servicio = '.$Id_Tipo_Servicio.' WHERE Id_Dispensacion = '.$id_dispensacion;
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $oCon->createData();

}

echo json_encode(['data'=>$Id_Tipo_Servicio]);