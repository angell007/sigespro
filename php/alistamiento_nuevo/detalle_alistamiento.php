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
    $condicion .= " AND Codigo LIKE '%$_REQUEST[cod]%'";
}

if (isset($_REQUEST['tipo']) && $_REQUEST['tipo'] != "") {
    $condicion .= " AND Tipo='$_REQUEST[tipo]'";
}

if (isset($_REQUEST['origen']) && $_REQUEST['origen'] != "") {
    $condicion .= " AND Nombre_Origen LIKE '%$_REQUEST[origen]%'";
}

if (isset($_REQUEST['destino']) && $_REQUEST['destino'] != "") {
    $condicion .= " AND Nombre_Destino LIKE '%$_REQUEST[destino]%'";
}

if (isset($_REQUEST['est']) && $_REQUEST['est'] != "") {
    $condicion .= " AND Estado='$_REQUEST[est]'";
}

if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "") {
    $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
    $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);

    $condicion .= " AND DATE_FORMAT(Fecha,'%Y-%m-%d') BETWEEN  '$fecha_inicio' AND '$fecha_fin'";
}

$condicion_principal = "WHERE Estado_Alistamiento=2";

if (isset($_REQUEST['fases']) && $_REQUEST['fases'] == 1) {
    $condicion_principal = "WHERE Estado_Alistamiento=0";
} elseif (isset($_REQUEST['fases']) && $_REQUEST['fases'] == 2) {
    $condicion_principal = "WHERE Estado_Alistamiento=1";
}

$query = 'SELECT 
            COUNT(*) AS Total
          FROM Remision
          '.$condicion_principal.' ' . $condicion ;

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

$query = 'SELECT 
            *, (SELECT COUNT(*) FROM Producto_Remision PR WHERE PR.Id_Remision = R.Id_Remision) as Items
          FROM Remision R
          '.$condicion_principal.' ' . $condicion . 'AND Id_Inventario_Nuevo !=NULL  ORDER BY Codigo DESC, Fecha DESC LIMIT ' . $limit . ',' . $tamPag  ;
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$remisiones['remisiones'] = $oCon->getData();
unset($oCon);

$i=-1;
foreach($remisiones['remisiones'] as $remision){ $i++;

    $oItem = new complex($remision["Tipo_Origen"],"Id_".$remision["Tipo_Origen"],$remision["Id_Origen"]);
    $or=$oItem->getData();
    unset($oItem);
    $remisiones['remisiones'][$i]["NombreOrigen"]=$or["Nombre"];
    
    $oItem = new complex($remision["Tipo_Destino"],"Id_".$remision["Tipo_Destino"],$remision["Id_Destino"]);
    $or=$oItem->getData();
    unset($oItem);
    $remisiones['remisiones'][$i]["NombreDestino"]=$or["Nombre"];
}

$remisiones['numReg'] = $numReg;

echo json_encode($remisiones);
?>