<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query='SELECT COUNT(*) AS Total
        FROM Contrato C
INNER JOIN Cliente CL
On C.Id_Cliente=CL.Id_Cliente';

$oCon= new consulta();
$oCon->setQuery($query);
$total = $oCon->getData();
unset($oCon);


####### PAGINACIÓN ######## 
$tamPag = 10; 
$numReg = $total["Total"]; 
$paginas = ceil($numReg/$tamPag); 
$limit = ""; 
$paginaAct = "";

if (!isset($_REQUEST['pag']) || $_REQUEST['pag'] == '') { 
    $paginaAct = 1; 
    $limit = 0; 
} else { 
    $paginaAct = $_REQUEST['pag']; 
    $limit = ($paginaAct-1) * $tamPag; 
} 

$query='SELECT C.*, CL.Nombre as Nombre_Cliente
FROM Contrato C
INNER JOIN Cliente CL
On C.Id_Cliente=CL.Id_Cliente LIMIT '.$limit.','.$tamPag;
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$contratos['contratos'] = $oCon->getData();
unset($oCon);

$contratos['numReg'] = $numReg;

echo json_encode($contratos);
?>