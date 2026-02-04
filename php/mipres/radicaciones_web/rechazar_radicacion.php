<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../../class/class.complex_prohsa.php');
include_once('../../../class/class.mensajes.php');
date_default_timezone_set('America/Bogota');


$id = (isset($_REQUEST['id']) ? $_REQUEST['id'] : '');
$causa_rechazo = (isset($_REQUEST['causa_rechazo']) ? $_REQUEST['causa_rechazo'] : '');

if ($id && $causa_rechazo) {
    
    $oItem=new complex2('radicacion','id',$id);
    $rad = $oItem->getData();
    $oItem->Estado='Rechazado';
    $oItem->Causa_Rechazo=$causa_rechazo;
    $oItem->Fecha_Rechazo=date("Y-m-d H:i:s");
    $oItem->save();
    unset($oItem);
    
    
    if($rad["Celular_Reclamante"]!=""){
        $texto=$rad["Nombre_Reclamante"].", su Radicacion Web ".$rad["Codigo"]." ha sido rechazada, por favor consulte la plataforma. Gracias por utilizar nuestros servicios, ProH S.A.";
        $oCon= new Mensaje();
        $resultado=$oCon->Enviar($rad["Celular_Reclamante"],$texto);

    }
    


    $response['tipo'] = "success";
    $response['tiulo'] = "Radicación Web";
    $response['mensaje'] = "Radicación rechazada con éxito!";
    echo json_encode($response);

}