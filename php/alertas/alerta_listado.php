<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$query = 'SELECT COUNT(*) AS Total FROM Alerta'.$condicion;

$oCon= new consulta();

$oCon->setQuery($query);
$total = $oCon->getData();
unset($oCon);

####### PAGINACIÓN ######## 
$tamPag = 20; 
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

$query = 'SELECT A.*, DATE_FORMAT(Fecha, "%d/%m/%Y") AS Fechas, 
			CONCAT(F.Nombres, " ", F.Apellidos) as Funcionario
            FROM Alerta A 
            INNER JOIN Funcionario F ON A.Identificacion_Funcionario=F.Identificacion_Funcionario'.$condicion.' ORDER BY  Fechas DESC LIMIT '.$limit.','.$tamPag.'';

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$alerta['alertas'] = $oCon->getData();
unset($oCon);


$alerta['numReg'] = $numReg;

echo json_encode($alerta);
?>