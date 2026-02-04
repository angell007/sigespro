<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');


require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = 'SELECT 
    CONCAT(F.Nombres, " ", F.Apellidos) AS Nombre, 
    CONCAT(F.Identificacion_Funcionario, " - " , F.Nombres, " ", F.Apellidos) AS label, F.Identificacion_Funcionario,  
    (SELECT C.Valor FROM Contrato_Funcionario C WHERE C.Identificacion_Funcionario = F.Identificacion_Funcionario AND C.Estado = "Activo" LIMIT 1) as Valor_Contrato,
    F.Identificacion_Funcionario as value
    
FROM Funcionario F WHERE F.Liquidado="NO" AND F.Suspendido="NO" ';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);

echo json_encode($resultado);
?>