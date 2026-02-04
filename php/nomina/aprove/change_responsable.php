<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../../class/class.complex.php');

#/home/sigespro/public_html/php/nomina/aprove/get_status.php/php
$fill = ( isset( $_REQUEST['Fill'] ) ? $_REQUEST['Fill'] : '' );
$value = ( isset( $_REQUEST['Identificacion_Funcionario'] ) ? $_REQUEST['Identificacion_Funcionario'] : '' );

if($fill && $value){
$oItem = new complex("Configuracion","Id_Configuracion",1);
$oItem->$fill = $value;
$oItem->save();

unset($oItem);
    echo json_encode (['actualizado con exito']);
}



    