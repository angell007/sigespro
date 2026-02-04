<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

// var_dump($_REQUEST);exit;
/*$oLista = new lista("Funcionario");
$oLista->setRestrict("Id_Dependencia","=",$id);
$Funcionarios= $oLista->getlist();*/

$query = 'SELECT  CONCAT(F.Nombres, " ", F.Apellidos) as NombreF, F.Identificacion_Funcionario, CONCAT(F.Nombres, " ", F.Apellidos) as label, F.Identificacion_Funcionario as value
          FROM Funcionario F 
          INNER JOIN Contrato_Funcionario CF ON CF.Identificacion_Funcionario = F.Identificacion_Funcionario
          WHERE F.Tipo != "Externo" AND CF.Estado="Activo" AND F.Liquidado = "NO" AND F.Id_Dependencia='.$id;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$Funcionarios = $oCon->getData(); 
unset($oCon);

echo json_encode($Funcionarios);
?>