<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/class.contabilizar.php');

$tipo_comprobante = isset($_REQUEST['Tipo_Comprobante']) ? $_REQUEST['Tipo_Comprobante'] : false;
$funcionario = isset($_REQUEST['Identificacion_Funcionario']) ? $_REQUEST['Identificacion_Funcionario'] : false;
$resultado = [];

if ($tipo_comprobante && $funcionario) {
    $query = "SELECT Id_Borrador_Contabilidad AS ID, CONCAT(BC.Codigo, ' | ', CONCAT_WS(' ',F.Nombres,F.Apellidos), ' | ', DATE_FORMAT(Created_At,'%d/%m/%Y %H:%i:%s')) AS Title FROM Borrador_Contabilidad BC INNER JOIN Funcionario F ON F.Identificacion_Funcionario = BC.Identificacion_Funcionario WHERE Estado = 'Activa' AND Tipo_Comprobante = '$tipo_comprobante' AND BC.Identificacion_Funcionario = $funcionario";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $resultado = $oCon->getData();
    unset($oCon);
}

echo json_encode($resultado);

          
?>