<?php

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');


$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );


$oItem = new complex('Alerta','Id_Alerta',$datos["Id_Alerta"]);
$oItem->Respuesta="Si";
$oItem->save();
unset($oItem);


$oItem = new complex('Llegada_Tarde','Id_Llegada_Tarde',$datos["Id_Llegada_Tarde"]);
$oItem->Cuenta=$datos["Cuenta"];
$oItem->Justificacion=$datos["Justificacion"];
$oItem->save();
unset($oItem);


echo "Información Actualizada Exitosamente";

?>