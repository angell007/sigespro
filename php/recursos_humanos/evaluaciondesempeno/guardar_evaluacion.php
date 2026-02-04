<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$evaluacion = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$evaluacion = (array) json_decode($evaluacion, true);
$funcionario = $evaluacion['Funcionario']['Identificacion_Funcionario'];

if ($evaluacion['Id_Evaluacion_Desempeno']) {
    $oItem = new complex('Evaluacion_Desempeno',"Id_Evaluacion_Desempeno",$evaluacion['Id_Evaluacion_Desempeno']);
}else{
    $oItem = new complex('Evaluacion_Desempeno',"Id_Evaluacion_Desempeno");
}

foreach($evaluacion as $index=>$value) {
    $oItem->$index=$value;
}
$oItem->Identificacion_Funcionario=$funcionario;
$oItem->save();
unset($oItem);

$resultado["Mensaje"]="Evaluación Guardada Correctamente";      
$resultado["Titulo"]="Operacion Exitosa";      
$resultado["Tipo"]="success";      
     
echo json_encode($resultado);
