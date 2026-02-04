<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condicion = '';

if (isset($_REQUEST['func']) && $_REQUEST['func'] != "") {
    $condicion .= " AND D.Facturador_Asignado = $_REQUEST[func]";
}

if (isset($_REQUEST['cod']) && $_REQUEST['cod'] != "") {
    $condicion .= " AND D.Codigo LIKE '%$_REQUEST[cod]%'";
}
if (isset($_REQUEST['tipo']) && $_REQUEST['tipo'] != "") {
    $condicion .= " AND D.Tipo='$_REQUEST[tipo]'";
}
if (isset($_REQUEST['est']) && $_REQUEST['est'] != "") {
    $condicion .= " AND D.Estado_Facturacion='$_REQUEST[est]'";
}
if (isset($_REQUEST['facturador']) && $_REQUEST['facturador'] != "") {
    $condicion .= " AND IFNULL(CONCAT(F.Nombres, ' ', F.Apellidos),'No Asignado') LIKE '%$_REQUEST[facturador]%'";
}
if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "") {
    $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
    $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
    $condicion .= " AND Fecha_Actual BETWEEN '$fecha_inicio' AND '$fecha_fin'";
}

$query = 'SELECT COUNT(*) AS Total
          FROM Dispensacion D
          LEFT JOIN Funcionario F
          on D.Facturador_Asignado=F.Identificacion_Funcionario
          INNER JOIN Punto_Dispensacion P
          on D.Id_Punto_Dispensacion=P.Id_Punto_Dispensacion
          INNER JOIN Departamento L
          on P.Departamento=L.Id_Departamento
          WHERE D.Id_Tipo_Servicio!=7
          '.$condicion;

$oCon= new consulta();

$oCon->setQuery($query);
$dispensaciones = $oCon->getData();
unset($oCon);

####### PAGINACIÓN ######## 
$tamPag = 10; 
$numReg = $dispensaciones["Total"]; 
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


$query = 'SELECT D.*, DATE_FORMAT(D.Fecha_Actual, "%d/%m/%Y") as Fecha, 
                 IFNULL(CONCAT(F.Nombres, " ", F.Apellidos),"No Asignado") as Funcionario, 
                 P.Nombre as Punto_Dispensacion, L.Nombre as Departamento 
          FROM Dispensacion D
          LEFT JOIN Funcionario F
          on D.Facturador_Asignado=F.Identificacion_Funcionario
          INNER JOIN Punto_Dispensacion P
          on D.Id_Punto_Dispensacion=P.Id_Punto_Dispensacion
          INNER JOIN Departamento L
          on P.Departamento=L.Id_Departamento
          WHERE D.Id_Tipo_Servicio!=7
          '.$condicion.'
          ORDER BY D.Estado_Facturacion DESC,D.Fecha_Actual DESC LIMIT '.$limit.','.$tamPag;


$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$dispensaciones["dispensaciones"] = $oCon->getData();
unset($oCon);

$dispensaciones["numReg"] = $numReg;

echo json_encode($dispensaciones);
?>