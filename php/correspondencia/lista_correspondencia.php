<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


$condicion = '';

if (isset($_REQUEST['cod']) && $_REQUEST['cod'] != '') {
    $condicion .= "WHERE C.Id_Correspondencia = " . str_replace('CO000','',$_REQUEST['cod']);
}

if (isset($_REQUEST['guia']) && $_REQUEST['guia'] != "") {
    if ($condicion != "") {
        $condicion .= " AND (C.Numero_Guia = '$_REQUEST[guia]' OR C.Empresa_Envio = '$_REQUEST[guia]')";
    } else {
        $condicion .= "WHERE (C.Numero_Guia = '$_REQUEST[guia]' OR C.Empresa_Envio = '$_REQUEST[guia]')";
    }
}

if (isset($_REQUEST['est']) && $_REQUEST['est'] != "") {
    if ($condicion != "") {
        $condicion .= " AND C.Estado = '$_REQUEST[est]'";
    } else {
        $condicion .= "WHERE C.Estado = '$_REQUEST[est]'";
    }
}

if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "" && $_REQUEST['fecha'] != "undefined") {
    if ($condicion != "") {
        $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
        $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
        $condicion .= " AND (DATE_FORMAT(Fecha_Envio, '%Y-%m-%d') BETWEEN '$fecha_inicio' AND '$fecha_fin')";
    } else {
        $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
        $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
        $condicion .= " WHERE DATE_FORMAT(Fecha_Envio, '%Y-%m-%d') BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    }
}

if (isset($_REQUEST['disp']) && $_REQUEST['disp'] != "" && $_REQUEST['disp'] != "undefined") {
    if ($condicion != "") {       
        $condicion .= " AND D.Codigo LIKE '$_REQUEST[disp]%')";
    } else {
        $condicion .= " WHERE D.Codigo LIKE '$_REQUEST[disp]%' ";
    }
}

$query='SELECT COUNT(*) AS Total
From Correspondencia C
INNER JOIN Funcionario F
ON C.Id_Funcionario_Envia=F.Identificacion_Funcionario
INNER JOIN Dispensacion D ON C.Id_Correspondencia=D.Id_Correspondencia 
 '.$condicion;

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

$query='SELECT C.*, CONCAT(F.Nombres," ", F.Apellidos) as Funcionario_Envio, F.Imagen
From Correspondencia C
INNER JOIN Funcionario F
ON C.Id_Funcionario_Envia=F.Identificacion_Funcionario 
INNER JOIN Dispensacion D ON C.Id_Correspondencia=D.Id_Correspondencia '.$condicion . ' LIMIT ' . $limit . ',' . $tamPag;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado['Correspondencia'] = $oCon->getData();
unset($oCon);

$resultado['numReg'] = $numReg;

echo json_encode($resultado);
?>