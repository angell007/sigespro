<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condicion = '';

if (isset($_REQUEST['tipo_contrato']) && $_REQUEST['tipo_contrato'] != "") {
    $condicion .= " WHERE C.Tipo_Contrato LIKE '%$_REQUEST[tipo_contrato]%'";
}

if (isset($_REQUEST['nombre_contrato']) && $_REQUEST['nombre_contrato']) {
    if ($condicion != "") {
        $condicion .= " AND C.Nombre_Contrato LIKE '%$_REQUEST[nombre_contrato]%'";
    } else {
        $condicion .= " WHERE C.Nombre_Contrato LIKE '%$_REQUEST[nombre_contrato]%'";
    }
}

if (isset($_REQUEST['nombre_cliente']) && $_REQUEST['nombre_cliente']) {
    if ($condicion != "") {
        $condicion .= " AND CL.Nombre LIKE '%$_REQUEST[nombre_cliente]%'";
    } else {
        $condicion .= " WHERE CL.Nombre LIKE '%$_REQUEST[nombre_cliente]%'";
    }
}

if (isset($_REQUEST['fecha_inicio']) && $_REQUEST['fecha_inicio']) {
    if ($condicion != "") {
        $condicion .= " AND C.Fecha_Inicio = '$_REQUEST[fecha_inicio]'";
    } else {
        $condicion .= " WHERE C.Fecha_Inicio = '$_REQUEST[fecha_inicio]'";
    }
}

if (isset($_REQUEST['fecha_fin']) && $_REQUEST['fecha_fin']) {
    if ($condicion != "") {
        $condicion .= " AND C.Fecha_Fin = '$_REQUEST[fecha_fin]'";
    } else {
        $condicion .= " WHERE C.Fecha_Fin = '$_REQUEST[fecha_fin]'";
    }
}

if (isset($_REQUEST['presupuesto']) && $_REQUEST['presupuesto']) {
    if ($condicion != "") {
        $condicion .= " AND C.Presupuesto LIKE $_REQUEST[presupuesto]";
    } else {
        $condicion .= " WHERE C.Presupuesto LIKE $_REQUEST[presupuesto]";
    }
}

$query='SELECT COUNT(*) AS Total
FROM Contrato C
INNER JOIN Cliente CL
On C.Id_Cliente=CL.Id_Cliente'.$condicion;

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
On C.Id_Cliente=CL.Id_Cliente'.$condicion.' LIMIT '.$limit.','.$tamPag;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$contratos['contratos'] = $oCon->getData();
unset($oCon);

$contratos['numReg'] = $numReg;

echo json_encode($contratos);
?>