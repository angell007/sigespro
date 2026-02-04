<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$Id_Envio_Evaluacion_Funcionario = ( isset( $_REQUEST['Id_Envio_Evaluacion_Funcionario'] ) ? $_REQUEST['Id_Envio_Evaluacion_Funcionario'] : '' );
$modulo = ( isset( $_REQUEST['Modulo'] ) ? $_REQUEST['Modulo'] : '' );

$condicion = '';

if($modulo == 'Usuario'){
   $condicion .= "EEF.Id_Envio_Evaluacion_Funcionario = $id ";
}else{
   $condicion .= "EEF.Id_Envio_Evaluacion_Funcionario = $Id_Envio_Evaluacion_Funcionario ";
}
$query = 'SELECT EF.Id_Encabezado_Formulario, EE.Id_Envio_Evaluacion, EEF.Id_Envio_Evaluacion_Funcionario
            FROM Envio_Evaluacion_Funcionario EEF
            INNER JOIN Envio_Evaluacion EE ON EEF.Id_Envio_Evaluacion = EE.Id_Envio_Evaluacion
            INNER JOIN Evaluacion_Desempeno ED ON EE.Id_Evaluacion_Desempeno = ED.Id_Evaluacion_Desempeno
            INNER JOIN Encabezado_Formulario EF ON ED.Id_Encabezado_Formulario = EF.Id_Encabezado_Formulario
            WHERE '.$condicion.' GROUP BY EF.Id_Encabezado_Formulario  ORDER by EF.Id_Encabezado_Formulario DESC LIMIT 1  ';
            
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('simple');
$id = $oCon->getData();
$datos['Evaluacion']= $oCon->getData();

unset($oCon);

$id = $id['Id_Encabezado_Formulario'];

if ($id){
    $query = 'SELECT * 
                FROM Encabezado_Formulario 
                WHERE Id_Encabezado_Formulario = '.$id.'';
    $oCon= new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $datos['Encabezado']= $oCon->getData();
    unset($oCon);

    $query = 'SELECT * 
                FROM Pregunta_Formulario
                WHERE Id_Encabezado_Formulario = '.$id.'';
    $oCon= new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $datos['Preguntas']= $oCon->getData();
    unset($oCon);

    foreach ($datos['Preguntas'] as $key => $value) {
        $query = 'SELECT * 
                    FROM Respuesta_Formulario
                    WHERE Id_Pregunta_Formulario = "'. $value['Id_Pregunta_Formulario'].'"';
        $oCon= new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');

       $datos['Preguntas'][$key]['Respuestas'] = $oCon->getData();
    }
}

echo json_encode($datos);

