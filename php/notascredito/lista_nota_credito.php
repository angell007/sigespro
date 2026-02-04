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
    $condicion .= " WHERE NC.Codigo LIKE '%$_REQUEST[cod]%'";
}
if($condicion!=''){
    if (isset($_REQUEST['cliente']) && $_REQUEST['cliente'] != "") {
        $condicion .= " AND C.Nombre LIKE '%$_REQUEST[cliente]%'";
    }
}else{
    if (isset($_REQUEST['cliente']) && $_REQUEST['cliente'] != "") {
        $condicion .= " WHERE C.Nombre LIKE '%$_REQUEST[cliente]%'";
    }
}
if($condicion!=''){
    if (isset($_REQUEST['estado']) && $_REQUEST['estado'] != "") {
        $condicion .= " AND NC.Estado LIKE '%$_REQUEST[estado]%'";
    }
}else{
    if (isset($_REQUEST['estado']) && $_REQUEST['estado'] != "") {
        $condicion .= " WHERE NC.Estado LIKE '%$_REQUEST[estado]%'";
    }
}
if($condicion!=''){
    if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "") {
        $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
        $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
        $condicion .= " AND DATE(NC.Fecha) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    }
}else{
    if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "") {
        $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
        $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
        $condicion .= " WHERE DATE(NC.Fecha) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    }
}
if($condicion!=''){
    
    if (isset($_REQUEST['fact']) && $_REQUEST['fact'] != "") {
        $condicion .= " AND FV.Codigo LIKE '%$_REQUEST[fact]%'";
}
}else{
    if (isset($_REQUEST['fact']) && $_REQUEST['fact'] != "") {
         $condicion .= " WHERE FV.Codigo LIKE '%$_REQUEST[fact]%'";
}
}

$query = 'SELECT COUNT(*) AS Total
FROM Nota_Credito NC 
INNER JOIN Factura_Venta FV 
ON  FV.Id_Factura_Venta = NC.Id_Factura
INNER JOIN Funcionario F 
ON NC.Identificacion_Funcionario=F.Identificacion_Funcionario
INNER JOIN Cliente C 
ON NC.Id_Cliente=C.Id_Cliente
'.$condicion ;

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

$query = 'SELECT NC.*, FV.Codigo as Factura, FV.Id_Resolucion, F.Imagen, C.Nombre 
FROM Nota_Credito NC 
INNER JOIN Factura_Venta FV 
ON  FV.Id_Factura_Venta = NC.Id_Factura
INNER JOIN Funcionario F 
ON NC.Identificacion_Funcionario=F.Identificacion_Funcionario
INNER JOIN Cliente C 
ON NC.Id_Cliente=C.Id_Cliente
'.$condicion.'ORDER BY NC.Id_Nota_Credito DESC LIMIT '.$limit.','.$tamPag;


$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$notas['Notas'] = $oCon->getData();
unset($oCon);

$notas['numReg'] = $numReg;

echo json_encode($notas);
?>