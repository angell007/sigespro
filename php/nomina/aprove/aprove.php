<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');
date_default_timezone_set('America/Bogota');

$periodo = ( isset( $_REQUEST['Periodo'] ) ? $_REQUEST['Periodo'] : '' );
$status = ( isset( $_REQUEST['Status'] ) ? $_REQUEST['Status'] : '' );
$origin = ( isset( $_REQUEST['Origen'] ) ? $_REQUEST['Origen'] : '' );
$observation = ( isset( $_REQUEST['Descripcion'] ) ? $_REQUEST['Descripcion'] : '' );

$label = "Status_$origin";
$idAprove= '';
$aprove = getAprove($periodo);
    
    
if($aprove){
    updateAprove($aprove);
    $idAprove = $aprove['Id_Aprobacion_Nomina'];
    
}else{
   $idAprove = insertAprove();
}

insertActivity($idAprove);



function insertActivity($idAprove){
    global $origin, $status, $observation;
    $oItem = new complex("Actividad_Aprobacion_Nomina","Id_Actividad_Aprobacion_Nomina");
    $oItem->Origen = $origin;
    $oItem->Status = $status == 1 ? 'Aprobado' : 'Rechazado';
    $oItem->Descripcion = $status == 1 ? 'Se ha aprobado satisfactoriamente' : $observation;
    $oItem->Id_Aprobacion_Nomina = $idAprove;
    $oItem->save();
    unset($oItem);
    
}
function getAprove($periodo){
    $q = 'SELECT  * FROM Aprobacion_Nomina WHERE DATE(Periodo)= "'.$periodo.'"';
    $oCon = new consulta();
    $oCon->setQuery($q);
    return  $oCon->getData();
    
}
function insertAprove(){
    global $label,$status,$periodo;
    
    $oItem = new complex("Aprobacion_Nomina","Id_Aprobacion_Nomina");
    $oItem->$label = $status;
    $oItem->Periodo = $periodo;
    $oItem->save();
    $id = $oItem->getId();
    return $id ;    
}
function updateAprove($aprove){
    global $label,$status;
    
    $oItem = new complex("Aprobacion_Nomina","Id_Aprobacion_Nomina",$aprove['Id_Aprobacion_Nomina']);
    if($status == '0'){
        $oItem->Status_Contabilidad = '0';
        $oItem->Status_Rrhh = '0';
        $oItem->Status_Nomina = '0';
    
        
    }else{
        $oItem->$label = $status;
        
    }
    $oItem->save();
    unset($oItem);
    
    
}