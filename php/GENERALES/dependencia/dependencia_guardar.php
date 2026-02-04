<?php

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');


$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );


$oItem = new complex('Dependencia','Id_Dependencia',$id);
foreach($datos as $index=>$value) {
    $oItem->$index=$value;
}
$oItem->save();
unset($oItem);

echo "Dependencia Guardada Exitosamente";

?>