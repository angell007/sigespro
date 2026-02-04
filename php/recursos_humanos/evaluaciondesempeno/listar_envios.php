<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = 'SELECT EE.Id_Envio_Evaluacion, EF.Nombre as NombreFormulario,
                 EF.Descripcion, CONCAT(F.Nombres, " ", F.Apellidos) AS Auditor, 
                 C.Nombre as Cargo, 
                 DATE(EE.Fecha) AS Fecha
          FROM Envio_Evaluacion EE
          INNER JOIN Funcionario F ON F.Identificacion_Funcionario = EE.Id_Jefe
          INNER JOIN Evaluacion_Desempeno ED ON ED.Id_Evaluacion_Desempeno = EE.Id_Evaluacion_Desempeno
          INNER JOIN Cargo C ON C.Id_Cargo = ED.Id_Cargo
          INNER JOIN Encabezado_Formulario EF ON EF.Id_Encabezado_Formulario = ED.Id_Encabezado_Formulario
          WHERE EE.Id_Evaluacion_Desempeno = ' .$id;

     // WHERE EE.Id_Evaluacion_Desempeno = "'.$id.'" GROUP BY EE.Id_Evaluacion_Desempeno';
              
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$datos['Envios']= $oCon->getData();
unset($oCon);

echo json_encode($datos);
