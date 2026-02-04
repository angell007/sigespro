<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );
$Identificacion_Funcionario = ( isset( $_REQUEST['Identificacion_Funcionario'] ) ? $_REQUEST['Identificacion_Funcionario'] : '' );

$condicion = '';

if (isset($_REQUEST['detalle'])) {
    $condicion .= " NEF.Identificacion_Funcionario = '$_REQUEST[Identificacion_Funcionario]' AND";
}

      

$query="SELECT 
    LF.*,
    CONCAT(F.Nombres,' ', F.Apellidos) as Funcionario
    FROM Liquidacion_Funcionario LF
    INNER JOIN Funcionario F on F.Identificacion_Funcionario = LF.Identificacion_Funcionario
    ORDER BY LF.Id_Liquidacion_Funcionario DESC";


$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$funcionarios= $oCon->getData();
unset($oCon);


$resultado['Funcionarios']=$funcionarios;
$resultado['TotalReportados']=$TotalReportados;
$resultado['TotalReportadosExito']=$TotalReportadosExito;
$resultado['TotalReportadosError']=$TotalReportadosError;
$resultado['TotalReportadosPendiente']=$TotalReportadosPendiente;


echo json_encode($resultado);