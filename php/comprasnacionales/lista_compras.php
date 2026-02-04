<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condicion = 'WHERE 1 ';
$funcionario = ( isset( $_REQUEST['funcionario'] ) ? $_REQUEST['funcionario'] : '' );
$tipo = ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : '' );

if (isset($_REQUEST['est']) && $_REQUEST['est'] != "") {
  $condicion .= "AND OCN.Estado='$_REQUEST[est]'";
}

if (isset($_REQUEST['cod']) && $_REQUEST['cod'] != "") {
  $condicion .= " AND OCN.Codigo LIKE '%$_REQUEST[cod]%'";
}

if (isset($_REQUEST['prov']) && $_REQUEST['prov'] != "") {
  $condicion .= " AND P.Nombre LIKE '%$_REQUEST[prov]%'";
}
if (isset($_REQUEST['aprov']) && $_REQUEST['aprov'] != "") {
  $condicion .= " AND OCN.Aprobacion LIKE '$_REQUEST[aprov]'";
}

if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "" && $_REQUEST['fecha'] != "undefined") {
    $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
    $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
    $condicion .= " AND DATE_FORMAT(Fecha, '%Y-%m-%d') BETWEEN '$fecha_inicio' AND '$fecha_fin'";
}


if ($tipo != '' && $tipo == 'filtrado') {
  if ($funcionario != '') {
    $condicion .= " AND OCN.Identificacion_Funcionario = ".$funcionario." ";
  }
}  

if (isset($_REQUEST['func']) && $_REQUEST['func'] != "" && $_REQUEST['func'] != "undefined") {

   $condicion .= " AND ( OCN.Identificacion_Funcionario = '".$_REQUEST['func']."' OR  F.Nombres LIKE '%".$_REQUEST['func']."%')";
}



$query = 'SELECT COUNT(*) AS Total 
          FROM Orden_Compra_Nacional OCN
          left JOIN Proveedor P ON OCN.Id_Proveedor=P.Id_Proveedor
          INNER JOIN Funcionario F ON F.Identificacion_Funcionario = OCN.Identificacion_Funcionario ' 
          . $condicion 
          . ' ORDER BY OCN.Fecha DESC';

$oCon= new consulta();
$oCon->setQuery($query);
$total = $oCon->getData();
unset($oCon);

####### PAGINACIÓN ######## 
$tamPag = 20; 
$numReg = $total["Total"]; 
$paginas = ceil($numReg/$tamPag); 
$limit = ""; 
$limit = 0; 
$paginaAct = (isset($_REQUEST['pag']) && $_REQUEST['pag'] !== '') ? $_REQUEST['pag']  : 1;

$limit = ($paginaAct-1) * $tamPag; 

$query =    "SELECT OCN.*, P.Nombre as Proveedor, F.Imagen 
            FROM Orden_Compra_Nacional OCN
            left JOIN Proveedor P ON OCN.Id_Proveedor=P.Id_Proveedor 
            INNER JOIN Funcionario F ON OCN.Identificacion_Funcionario=F.Identificacion_Funcionario
            $condicion ORDER BY OCN.Fecha DESC LIMIT $limit,$tamPag";

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado['compras'] = $oCon->getData();
$resultado['conteo_compras'] = $numReg;
unset($oCon);

$resultado['numReg'] = $numReg;
          
echo json_encode($resultado);

?>