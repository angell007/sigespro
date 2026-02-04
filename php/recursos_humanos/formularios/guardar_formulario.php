<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$formulario = ( isset( $_REQUEST['Formulario'] ) ? $_REQUEST['Formulario'] : '' );
$preguntas = ( isset( $_REQUEST['Preguntas'] ) ? $_REQUEST['Preguntas'] : '' );

$formulario = (array) json_decode($formulario, true);
$preguntas = (array) json_decode($preguntas, true);

if($formulario['Id_Encabezado_Formulario']){
    $oItem = new complex('Encabezado_Formulario',"Id_Encabezado_Formulario",$formulario['Id_Encabezado_Formulario']);
}else{
    $oItem = new complex("Encabezado_Formulario","Id_Encabezado_Formulario");
}

foreach($formulario as $index=>$value) {
    $oItem->$index=$value;
}
$oItem->save();
$Id_Encabezado_Formulario = $oItem->getId(); 
unset($oItem);

foreach ($preguntas as $preg) {
    if($formulario['Id_Encabezado_Formulario']){
        $oItem = new complex("Pregunta_Formulario","Id_Pregunta_Formulario",$preg['Id_Pregunta_Formulario']);
    }else{
        $oItem = new complex("Pregunta_Formulario","Id_Pregunta_Formulario");
    }
    $oItem->Id_Encabezado_Formulario=$Id_Encabezado_Formulario;
    $oItem->Pregunta=$preg["Pregunta"];
    $oItem->Tipo=$preg["Tipo"];
    $oItem->Valor=$preg["Valor"];
    $oItem->save();
    $Id_Pregunta_Formulario = $oItem->getId();
    unset($oItem);    

    foreach ($preg['Respuestas'] as $clave => $valor) {
        if($formulario['Id_Encabezado_Formulario']){            
            $oItem = new complex("Respuesta_Formulario","Id_Respuesta_Formulario",$valor['Id_Respuesta_Formulario']);
        }else{
            $oItem = new complex("Respuesta_Formulario","Id_Respuesta_Formulario");
        }
            $oItem->Id_Pregunta_Formulario=$Id_Pregunta_Formulario;
            $oItem->Respuesta=$valor['Respuesta'];
            $oItem->Valor=$valor["Valor"];
            $oItem->save();
            unset($oItem);
}

$resultado["Mensaje"]="Formulario Gestionado Correctamente";      
$resultado["Titulo"]="Operacion Exitosa";      
$resultado["Tipo"]="success";      
     
}

echo json_encode($resultado);
