<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condicion = '';

if (isset($_REQUEST['est']) && $_REQUEST['est'] != "") {
  $condicion .= "WHERE Estado='$_REQUEST[est]'";
}

if ($condicion != "") {
  if (isset($_REQUEST['cod']) && $_REQUEST['cod'] != "") {
    $condicion .= " AND Codigo LIKE '%$_REQUEST[cod]%'";
  }
} else {
  if (isset($_REQUEST['cod']) && $_REQUEST['cod'] != "") {
    $condicion .= "WHERE Codigo LIKE '%$_REQUEST[cod]%'";
  }
}

if ($condicion != "") {
  if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "") {
      $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
      $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
      $condicion .= " AND Fecha_Creacion_Creacion BETWEEN '$fecha_inicio' AND '$fecha_fin'";
  }
} else {
  if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "") {
      $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
      $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
      $condicion .= "WHERE Fecha_Creacion_Creacion BETWEEN '$fecha_inicio' AND '$fecha_fin'";
  } 
}

$query = "SELECT COUNT(*) AS Total FROM Orden_Compra_Internacional " . $condicion;

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

$query = "SELECT * FROM Orden_Compra_Internacional $condicion ORDER BY Fecha_Registro DESC, Codigo DESC LIMIT $limit,$tamPag";

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado['compras'] = $oCon->getData();
unset($oCon);

$resultado['numReg'] = $numReg;

echo json_encode($resultado);

?>