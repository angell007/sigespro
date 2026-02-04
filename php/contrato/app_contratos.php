<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );

$query='SELECT CF.*, DATE_FORMAT(CF.Fecha_Inicio_Contrato,"%d de %M del %Y") as Inicio, DATE_FORMAT(CF.Fecha_Fin_Contrato,"%d de %M del %Y") as Fin, TC.Nombre, R.Nombre as Riesgo
FROM Contrato_Funcionario CF
INNER JOIN Tipo_Contrato TC ON CF.Id_Tipo_Contrato = TC.Id_Tipo_Contrato
INNER JOIN Riesgo R ON R.Id_Riesgo=CF.Id_Riesgo
WHERE Identificacion_Funcionario='.$funcionario;


$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$contratos = $oCon->getData();
unset($oCon);

echo json_encode($contratos);
?>