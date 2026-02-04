<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condicion = '';

if (isset($_REQUEST['cod_fact']) && $_REQUEST['cod_fact'] != "") {
    $condicion .= "WHERE F.Codigo LIKE '%$_REQUEST[cod_fact]%'";
}
if (isset($_REQUEST['estado_fact']) && $_REQUEST['estado_fact']) {
    if ($condicion != "") {
        $condicion .= " AND F.Estado_Factura='$_REQUEST[estado_fact]'";
    } else {
        $condicion .= "WHERE F.Estado_Factura='$_REQUEST[estado_fact]'";
    }
}
if ($condicion != "") {
    if (isset($_REQUEST['fecha_fact']) && $_REQUEST['fecha_fact'] != "") {
        $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha_fact'])[0]);
        $fecha_fin = trim(explode(' - ', $_REQUEST['fecha_fact'])[1]);
        $condicion .= " AND F.Fecha_Documento BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    }
} else {
    if (isset($_REQUEST['fecha_fact']) && $_REQUEST['fecha_fact'] != "") {
        $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha_fact'])[0]);
        $fecha_fin = trim(explode(' - ', $_REQUEST['fecha_fact'])[1]);
        $condicion .= "WHERE F.Fecha_Documento BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    } 
}

$query2 = 'SELECT 
            COUNT(*) AS Total
           FROM `Factura` F 
           INNER JOIN Cliente C 
           ON C.Id_Cliente = F.`Id_Cliente` ' . $condicion ;

$oCon= new consulta();
$oCon->setQuery($query2);
$productos = $oCon->getData();
unset($oCon);

####### PAGINACIÓN ######## 
$tamPag = 10; 
$numReg = $productos["Total"]; 
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

$query2 = 'SELECT 
            C.Nombre as Nombre , F.`Id_Factura` as Id_Factura  , F.`Fecha_Documento` as Fecha_Documento , F.`Estado_Factura` as Estado_Factura , F.`Codigo` as Codigo
           FROM `Factura` F 
           INNER JOIN Cliente C 
           ON C.Id_Cliente = F.`Id_Cliente` '.$condicion.' LIMIT '.$limit.','.$numReg ;

$oCon= new consulta();
$oCon->setQuery($query2);
$oCon->setTipo('Multiple');
$productos['productos'] = $oCon->getData();
unset($oCon);

$productos['numReg'] = $numReg;

echo json_encode($productos);
