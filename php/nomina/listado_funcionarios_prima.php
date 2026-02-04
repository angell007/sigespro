<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$Id_Prima = ( isset( $_REQUEST['Id_Prima'] ) ? $_REQUEST['Id_Prima'] : '' );

$query = "SELECT CONCAT(F.Nombres,' ', Apellidos) as Funcionario, PF.Salario, PF.Dias_Trabajados, PF.Total_Prima
            FROM Prima_Funcionario PF
            INNER JOIN Prima P ON P.Id_Prima = PF.Id_Prima
            INNER JOIN Funcionario F ON F.Identificacion_Funcionario = PF.Identificacion_Funcionario
            WHERE P.Id_Prima = $Id_Prima ";
$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado["FuncionariosPrima"] = $oCon->getData();
unset($oCon);

echo json_encode($resultado);
