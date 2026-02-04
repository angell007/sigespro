<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');

$punto = ( isset( $_REQUEST['Punto'] ) ? $_REQUEST['Punto'] : '' );
$caja = ( isset( $_REQUEST['Caja'] ) ? $_REQUEST['Caja'] : '' );

$oLista = new Lista("Turnero");
$oLista->setRestrict("Fecha","=",date("Y-m-d"));
$oLista->setRestrict("Id_Turneros","=",$punto);
$oLista->setRestrict("Estado","=","Espera");
$oLista->setOrder("Hora_Turno","ASC");
$turnos = $oLista->getList();
unset($oLista);


$oItem = new complex("Turnero","Id_Turnero",$turnos[0]["Id_Turnero"]);
$oItem->Estado="Atencion";
$oItem->Caja=$caja;
$oItem->Orden = 0;
$oItem->save();
$aten = $oItem->getData();
unset($oItem);

$oItem = new complex("Auditoria","Id_Auditoria",$aten["Id_Auditoria"]);
$audi = $oItem->getData();
unset($oItem);

if(isset($audi["Id_Auditoria"])){
    $aten["Documento"]=$audi["Archivo"];
    $aten["Id_Paciente"]=$audi["Id_Paciente"];
    $aten["Id_Tipo_Servicio"]=$audi["Id_Tipo_Servicio"];
    if($audi["Id_Tipo_Servicio"]==4){
        $aten["Tipo_Valor"]="Evento";
    }else{
        $aten["Tipo_Valor"]="NoPos";
    }
}else{
    $aten["Documento"]=''; 
    $aten["Documento"]='';
    $aten["Id_Paciente"]='';
    $aten["Tipo_Valor"]='';
    $aten["Id_Tipo_Servicio"]='';
}

$final["Turno"]=$aten;

echo json_encode($final);
?>