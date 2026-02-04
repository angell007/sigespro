<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condicion = '';

if (isset($_REQUEST['cod']) && $_REQUEST['cod'] != "") {
    $condicion .= "WHERE DC.Codigo LIKE '%$_REQUEST[cod]%'";
}
if (isset($_REQUEST['prov']) && $_REQUEST['prov'] != "") {
    if ($condicion != "") {
        $condicion .= " AND P.Nombre LIKE '%$_REQUEST[prov]%'";
    } else {
        $condicion .= "WHERE P.Nombre LIKE '%$_REQUEST[prov]%'";
    }
}
if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "") {
    $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
    $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
    if ($condicion != "") {
        $condicion .= " AND DC.Fecha BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    } else {
        $condicion .= "WHERE DC.Fecha BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    }
}

$query = 'SELECT COUNT(*) AS Total FROM Devolucion_Compra DC
INNER JOIN Funcionario F
ON DC.Identificacion_Funcionario=F.Identificacion_Funcionario
INNER JOIN Proveedor P
ON DC.Id_Proveedor=P.Id_Proveedor '. $condicion;

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

$query = "SELECT DC.*, F.Imagen, P.Nombre
 FROM Devolucion_Compra DC
INNER JOIN Funcionario F
ON DC.Identificacion_Funcionario=F.Identificacion_Funcionario
INNER JOIN Proveedor P
ON DC.Id_Proveedor=P.Id_Proveedor
$condicion
ORDER BY DC.Codigo DESC LIMIT ".$limit.','.$tamPag;
// echo $query;exit;
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$datos = $oCon->getData();
unset($oCon);

$resultado['numReg']=$numReg;
$resultado['devoluciones']=$datos;

echo json_encode($resultado);

?>