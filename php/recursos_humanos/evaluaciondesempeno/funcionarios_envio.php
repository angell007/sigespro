<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');


$Id_Funcionario = ( isset( $_REQUEST['Id_Funcionario'] ) ? $_REQUEST['Id_Funcionario'] : '' );
$Id_Envio_Evaluacion = ( isset( $_REQUEST['Id_Envio_Evaluacion'] ) ? $_REQUEST['Id_Envio_Evaluacion'] : '' );
$Id_Evaluacion_Desempeno = ( isset( $_REQUEST['Id_Evaluacion_Desempeno'] ) ? $_REQUEST['Id_Evaluacion_Desempeno'] : '' );


$query = 'SELECT 
              PF.Pregunta,
              TablaR.Id_Respuesta_Envio_Evaluacion,
              TablaR.Id_Envio_Evaluacion_Funcionario,
              EEF.Id_Envio_Evaluacion_Funcionario,
              EEF.Id_Envio_Evaluacion,
              EEF.Identificacion_Funcionario,
              #PREE.Id_Pregunta,
              PREE.Id_Preguntas_Respuesta_Envio_Evaluacion, 
                     TablaR.Valor as ValorPregunta,        
                     PREE.Valor as ValorRespuesta,
                     (( PREE.Valor  * TablaR.Valor ) / 100 ) as porcentaje, 
                     PREE.Respuesta 
              FROM Preguntas_Respuesta_Envio_Evaluacion PREE
              INNER JOIN Respuesta_Envio_Evaluacion TablaR ON TablaR.Id_Respuesta_Envio_Evaluacion = PREE.Id_Respuesta_Envio_Evaluacion 
              INNER JOIN Pregunta_Formulario PF ON PF.Id_Pregunta_Formulario = TablaR.Id_Pregunta
              INNER JOIN Envio_Evaluacion_Funcionario EEF ON TablaR.Id_Envio_Evaluacion_Funcionario = EEF.Id_Envio_Evaluacion_Funcionario    
              WHERE EEF.Id_Envio_Evaluacion = "'.$Id_Envio_Evaluacion.'" AND EEF.Identificacion_Funcionario = "'.$Id_Funcionario.'"  ';
     
     $query1 = $query;
     $query1 .= ' and PREE.Auditoria = 0';
     
     $oCon= new consulta();
     $oCon->setQuery($query1);
     $oCon->setTipo('Multiple');
     $datos['Usuario']= $oCon->getData();
     unset($oCon);
     
     $query.=' and PREE.Auditoria = 1' ;
     
     $oCon= new consulta();
     $oCon->setQuery($query);
     $oCon->setTipo('Multiple');
     $datos['Auditor']= $oCon->getData();
     unset($oCon);
     
     $query = 'SELECT * 
               FROM Evaluacion_Desempeno ED 
               WHERE ED.Id_Evaluacion_Desempeno = "'.$Id_Evaluacion_Desempeno.'"';

$oCon= new consulta();
$oCon->setQuery($query);
$datos['Evaluacion']= $oCon->getData();
unset($oCon);


echo json_encode($datos);

// $query = 'SELECT PF.Pregunta, 
//                  REE.Valor as ValorPregunta,
//                  PREE.Valor as ValorRespuesta,
//                  (( PREE.Valor  * REE.Valor ) / 100 ) as porcentaje, 
//                  PREE.Respuesta 
//           FROM Preguntas_Respuesta_Envio_Evaluacion PREE
//           INNER JOIN Pregunta_Formulario PF ON PF.Id_Pregunta_Formulario = PREE.Id_Pregunta
//           INNER JOIN Respuesta_Formulario RF ON RF.Id_Pregunta_Formulario = PF.Id_Pregunta_Formulario

//           INNER JOIN Respuesta_Envio_Evaluacion REE ON REE.Id_Pregunta = PREE.Id_Pregunta and REE.Id_Envio_Evaluacion_Funcionario = "'.$Id_Evaluacion_Desempeno.'"
//           INNER JOIN Envio_Evaluacion_Funcionario EEF ON EEF.Identificacion_Funcionario = REE.Id_Funcionario
//           INNER JOIN Envio_Evaluacion EE ON EE.Id_Envio_Evaluacion = EEF.Id_Envio_Evaluacion
//           WHERE EE.Id_Envio_Evaluacion = "'.$Id_Evaluacion_Desempeno.'" 
//                 AND PREE.Auditoria = 0 
//                 AND REE.Id_Funcionario = "'.$Id_Funcionario.'" 
//                 GROUP BY PREE.Id_Pregunta, PREE.Respuesta';
