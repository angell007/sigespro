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
    $condicion .= "WHERE Codigo LIKE '%$_REQUEST[cod]%'";
}

if ($condicion != "") {
    if (isset($_REQUEST['tipo']) && $_REQUEST['tipo'] != "") {
        $condicion .= " AND Nombre LIKE '%$_REQUEST[tipo]%'";
    }
} else {
    if (isset($_REQUEST['tipo']) && $_REQUEST['tipo'] != "") {
        $condicion .= "WHERE Nombre LIKE '%$_REQUEST[tipo]%'";
    }
}




$query = 'SELECT COUNT(*) AS Total FROM Tipo_Servicio ' . $condicion;

$oCon= new consulta();

$oCon->setQuery($query);
$total = $oCon->getData();
unset($oCon);

####### PAGINACIÓN ######## 
$tamPag = 15; 
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

$query = 'SELECT TS.* FROM Tipo_Servicio TS '.$condicion.' ORDER BY Codigo DESC LIMIT '.$limit.','.$tamPag;


$oCon= new consulta();

$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$tipo_servicios['Servicios'] = $oCon->getData();
unset($oCon);

$tipo_servicios['numReg'] = $numReg;




echo json_encode($tipo_servicios);
?>