<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$idalerta = ( isset( $_REQUEST['idalerta'] ) ? $_REQUEST['idalerta'] : '' );
$Id_Evaluacion = ( isset( $_REQUEST['Id_Evaluacion'] ) ? $_REQUEST['Id_Evaluacion'] : '' );


$datos = (array) json_decode($datos, true);
$idalerta = (array) json_decode($idalerta, true);


$IdEnvioEvaluacionFuncionario = 0;

if($idalerta["Modulo"] == 'Auditor'){
    $aud = 1;
    $IdEnvioEvaluacionFuncionario = $idalerta["Id_Envio_Evaluacion_Funcionario"];
    $oItem = new complex("Envio_Evaluacion_Funcionario","Id_Envio_Evaluacion_Funcionario", $idalerta["Id_Envio_Evaluacion_Funcionario"]);   
    $oItem->Estado = 1;
    $oItem->save();
    unset($oItem);
}else{
    $IdEnvioEvaluacionFuncionario = (int)$Id_Evaluacion;
    $aud = 0;
}

foreach ($datos as $dato) {
    $oItem = new complex("Respuesta_Envio_Evaluacion","Id_Respuesta_Envio");
    $oItem->Id_Funcionario=$idalerta["ids"];
    $oItem->Id_Envio_Evaluacion_Funcionario = $IdEnvioEvaluacionFuncionario;
    $oItem->Id_Pregunta=$dato["Pregunta"];
    $oItem->Valor=$dato["ValorP"];
    $oItem->save();
    $Id_Pregunta_Formulario = $oItem->getId();
    unset($oItem); 
    
    foreach ($dato['Respuesta'] as $valor) {
        $oItem = new complex("Preguntas_Respuesta_Envio_Evaluacion","Id_Pregunta_Respuesta_Envio");
        $oItem->Id_Respuesta_Envio_Evaluacion=$Id_Pregunta_Formulario;
        $oItem->Respuesta=$valor["Respuesta"];
        $oItem->Valor=$valor["ValorR"];
        $oItem->Auditoria=$aud;
        $oItem->save();
        unset($oItem);
}

$resultado["Mensaje"]="Formulario Gestionado Correctamente";      
$resultado["Titulo"]="Operacion Exitosa";      
$resultado["Tipo"]="success";      
     
}

try {
    $query = 'DELETE FROM Alerta WHERE id = ' . $IdEnvioEvaluacionFuncionario;
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->createData();
    unset($oCon);
} catch (\Throwable $th) {
    //throw $th;
    echo $th->getMessage();

}

echo json_encode($resultado);
