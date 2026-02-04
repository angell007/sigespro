<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$auditor = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = 'SELECT CONCAT(F.Nombres, " ", F.Apellidos) AS Funcionario, 
                 ED.Id_Evaluacion_Desempeno,
                 EE.Id_Envio_Evaluacion,
                 ED.Deficiente,
                 ED.minBueno,
                 ED.maxBueno,
                 ED.Excelente,
                 C.Nombre as Cargo, 
                 DATE(EE.Fecha) AS Fecha,
                 E.Nombre as Encabezado, 
                 EEF.Identificacion_Funcionario
            FROM Envio_Evaluacion_Funcionario EEF
            INNER JOIN Envio_Evaluacion EE ON EE.Id_Envio_Evaluacion = EEF.Id_Envio_Evaluacion
            INNER JOIN Evaluacion_Desempeno ED ON ED.Id_Evaluacion_Desempeno = EE.Id_Evaluacion_Desempeno
            INNER JOIN Encabezado_Formulario E ON E.Id_Encabezado_Formulario = ED.Id_Encabezado_Formulario
            INNER JOIN Cargo C ON C.Id_Cargo = ED.Id_Cargo
            INNER JOIN Funcionario F ON F.Identificacion_Funcionario = EEF.Identificacion_Funcionario
            WHERE EE.Id_Envio_Evaluacion = "'.$auditor.'" ORDER BY EEF.Id_Envio_Evaluacion_Funcionario DESC';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$datos['Listado']= $oCon->getData();
unset($oCon);



// $query = 'SELECT PF.Pregunta, 
//                  REE.Valor as ValorPregunta,
//                  PREE.Valor as ValorRespuesta,
//                  (( PREE.Valor  * REE.Valor ) / 100 ) as porcentaje, 
//                  PREE.Respuesta 
//           FROM Preguntas_Respuesta_Envio_Evaluacion PREE
//           INNER JOIN Pregunta_Formulario PF on PF.Id_Pregunta_Formulario = PREE.Id_Pregunta
//           INNER JOIN Respuesta_Formulario RF ON RF.Id_Pregunta_Formulario = PF.Id_Pregunta_Formulario
//           INNER JOIN Respuesta_Envio_Evaluacion REE ON REE.Id_Pregunta = PREE.Id_Pregunta
//           WHERE PREE.Auditoria = 0 and REE.Id_Funcionario = "'.$Id_Funcionario.'" GROUP BY PREE.Id_Pregunta, PREE.Respuesta';

// $oCon= new consulta();
// $oCon->setQuery($query);
// $oCon->setTipo('Multiple');
// $datos['Usuario']= $oCon->getData();
// unset($oCon);





echo json_encode($datos);

