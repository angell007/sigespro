<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');



$query = 'SELECT COUNT(*) AS Total FROM No_Conforme WHERE Tipo = "Compra" AND Estado="Pendiente"';

$oCon= new consulta();

$oCon->setQuery($query);
$total = $oCon->getData();
unset($oCon);

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

$query = "SELECT NC.*, F.Imagen, AR.Codigo as Codigo_Compra, P.Nombre, AR.Tipo, OCN.Codigo AS Codigo_Orden
 FROM No_Conforme NC
INNER JOIN Funcionario F
ON NC.Persona_Reporta=F.Identificacion_Funcionario
INNER JOIN Acta_Recepcion AR
ON NC.Id_Acta_Recepcion_Compra=AR.Id_Acta_Recepcion
INNER JOIN Orden_Compra_Nacional OCN
ON AR.Id_Orden_Compra_Nacional=OCN.Id_Orden_Compra_Nacional
INNER JOIN Proveedor P
ON AR.Id_Proveedor=P.Id_Proveedor
 WHERE NC.Tipo = 'Compra' AND NC.Estado='Pendiente' ORDER BY NC.Codigo DESC LIMIT ".$limit.','.$tamPag;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$datos = $oCon->getData();
unset($oCon);

$resultado['numReg']=$numReg;
$resultado['devoluciones']=$datos;

echo json_encode($resultado);

?>