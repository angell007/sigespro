<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = 'SELECT PF.Id_Pregunta_Formulario
            FROM Encabezado_Formulario EF
            INNER JOIN Pregunta_Formulario PF ON EF.Id_Encabezado_Formulario = PF.Id_Encabezado_Formulario
            INNER JOIN Respuesta_Formulario RF ON PF.Id_Pregunta_Formulario = RF.Id_Pregunta_Formulario
            WHERE EF.Id_Encabezado_Formulario = "'.$id.'"';
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo("simple");
$r = $oCon->getData();
unset($oCon);

$query = 'SELECT * 
            FROM Encabezado_Formulario ORDER BY Id_Encabezado_Formulario DESC';
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$datos['Lista'] = $oCon->getData();
unset($oCon);

if ($id) {
    $query = 'SELECT * 
                FROM Encabezado_Formulario 
                WHERE Id_Encabezado_Formulario = "'.$id.'"';
    $oCon= new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $datos['Encabezado']= $oCon->getData();
    unset($oCon);

    $query = 'SELECT * 
                FROM Pregunta_Formulario
                WHERE Id_Encabezado_Formulario = "'.$id.'"';
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

