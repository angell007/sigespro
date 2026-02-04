<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$mod = ( isset( $_REQUEST['modulo'] ) ? $_REQUEST['modulo'] : '' );
$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$dispensaciones = ( isset( $_REQUEST['dispensaciones'] ) ? $_REQUEST['dispensaciones'] : '' );

$datos = (array) json_decode($datos);
$dispensaciones = (array) json_decode($dispensaciones,true);

//$oItem = new complex($mod,"Id_".$mod);
if(isset($datos["id"])&&$datos["id"] != ""){
	$oItem = new complex($mod,"Id_".$mod,$datos["id"]);
}else{
	$oItem = new complex($mod,"Id_".$mod);
}
foreach($datos as $index=>$value) {
    $oItem->$index=$value;
}
$oItem->save();
$id_correspondencia = $oItem->getId();
unset($oItem);

/* $oItem=new complex("Alerta","Id_Alerta");
$oItem->Identificacion_Funcionario=$datos["Id_Funcionario_Envia"];
$oItem->Tipo="Correspondencia";
$oItem->Detalles="Se genero una Correspondencia con numero de Folios ".$datos["Cantidad_Folios"];
$oItem->save();
unset($oItem); */



foreach($dispensaciones as $dispensacion ){
    $oItem = new complex('Dispensacion',"Id_Dispensacion",$dispensacion);
    $oItem->Id_Correspondencia=$id_correspondencia;
    $oItem->Estado_Correspondencia="Enviada";
    $oItem->save();
    unset($oItem);
    $oItem=new complex("Actividades_Dispensacion","Id_Actividades_Dispensacion");
    $oItem->Id_Dispensacion=$dispensacion;
    $oItem->Identificacion_Funcionario=$datos["Id_Funcionario_Envia"];
    $oItem->Detalle="Se envio la correspondencia de esta Dispensacion ";
    $oItem->Estado="Enviada";
    $oItem->save();
    unset($oItem);
}

if ($id_correspondencia) {
    $resultado['mensaje'] = "Correspondencia creada satisfactoriamente";
    $resultado['title'] = "Exito!";
    $resultado['tipo'] = "success";
} else {
    $resultado['mensaje'] = "Ha ocurrido un error en la conexión. Por favor intentelo de nuevo.";
    $resultado['title'] = "Error!";
    $resultado['tipo'] = "error";
}

echo json_encode($resultado);
?>