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
  $condicion .= "WHERE OCN.Estado='$_REQUEST[est]'";
}

if ($condicion != "") {
  if (isset($_REQUEST['cod']) && $_REQUEST['cod'] != "") {
    $condicion .= " AND OCN.Codigo LIKE '%$_REQUEST[cod]%'";
  }
} else {
  if (isset($_REQUEST['cod']) && $_REQUEST['cod'] != "") {
    $condicion .= "WHERE OCN.Codigo LIKE '%$_REQUEST[cod]%'";
  }
}
if ($condicion != "") {
  if (isset($_REQUEST['prov']) && $_REQUEST['prov'] != "") {
    $condicion .= " AND P.Nombre LIKE '%$_REQUEST[prov]%'";
  }
} else {
  if (isset($_REQUEST['prov']) && $_REQUEST['prov'] != "") {
    $condicion .= "WHERE P.Nombre LIKE '%$_REQUEST[prov]%'";
  }
}
if ($condicion != "") {
  if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "") {
      $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
      $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
      $condicion .= " AND Fecha BETWEEN '$fecha_inicio' AND '$fecha_fin'";
  }
} else {
  if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "") {
      $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
      $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
      $condicion .= "WHERE Fecha BETWEEN '$fecha_inicio 00:00:00'  AND '$fecha_fin 23:59:59' ";
  } 
}

$query = 'SELECT COUNT(*) AS Total FROM Orden_Compra_Nacional OCN
INNER JOIN Proveedor P ON OCN.Id_Proveedor=P.Id_Proveedor INNER JOIN Funcionario F ON OCN.Identificacion_Funcionario=F.Identificacion_Funcionario '.$condicion.' AND OCN.Aprobacion="Aprobada"';

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

$query = 'SELECT OCN.*, P.Nombre as Proveedor, F.Imagen FROM Orden_Compra_Nacional OCN
INNER JOIN Proveedor P ON OCN.Id_Proveedor=P.Id_Proveedor INNER JOIN Funcionario F ON OCN.Identificacion_Funcionario=F.Identificacion_Funcionario '.$condicion.' AND OCN.Aprobacion="Aprobada" ORDER BY OCN.Fecha DESC LIMIT '.$limit.','.$tamPag;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado['compras'] = $oCon->getData();
unset($oCon);

$resultado['numReg'] = $numReg;
          
echo json_encode($resultado);

?>