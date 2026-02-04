<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$auditor = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = 'SELECT EEF.Id_Envio_Evaluacion_Funcionario, 
                 CONCAT(F.Nombres, " ", F.Apellidos) AS Nombre, 
                 C.Nombre as Cargo, 
                 EEF.Estado,
                 E.Nombre as Encabezado, 
                 EEF.Identificacion_Funcionario
            FROM Envio_Evaluacion_Funcionario EEF
            INNER JOIN Envio_Evaluacion EE ON EE.Id_Envio_Evaluacion = EEF.Id_Envio_Evaluacion
            INNER JOIN Evaluacion_Desempeno ED ON ED.Id_Evaluacion_Desempeno = EE.Id_Evaluacion_Desempeno
            INNER JOIN Encabezado_Formulario E ON E.Id_Encabezado_Formulario = ED.Id_Encabezado_Formulario

            INNER JOIN Cargo C ON C.Id_Cargo = ED.Id_Cargo
            INNER JOIN Funcionario F ON F.Identificacion_Funcionario = EEF.Identificacion_Funcionario
            WHERE EE.Id_Jefe = "'.$auditor.'"  ORDER BY EEF.Id_Envio_Evaluacion_Funcionario DESC';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$datos['Funcionarios']= $oCon->getData();
unset($oCon);

echo json_encode($datos);

