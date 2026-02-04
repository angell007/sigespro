<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once '../../config/start.inc.php';
include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';

$condicion = '';
$condiciones = ['True'];

if (isset($_REQUEST['cod']) && $_REQUEST['cod'] != "") {
    array_push($condiciones, "Codigo LIKE '%$_REQUEST[cod]%'");
}
if (isset($_REQUEST['tipo']) && $_REQUEST['tipo'] != "") {
    array_push($condiciones, " Tipo='$_REQUEST[tipo]'");
}

if (isset($_REQUEST['origen']) && $_REQUEST['origen'] != "") {
    array_push($condiciones, " Nombre_Origen LIKE '%$_REQUEST[origen]%'");
}
if (isset($_REQUEST['grupo']) && $_REQUEST['grupo'] != "") {

    array_push($condiciones, " G.Nombre LIKE '%$_REQUEST[grupo]%'");
}

if (isset($_REQUEST['destino']) && $_REQUEST['destino'] != "") {
    array_push($condiciones, " Nombre_Destino LIKE '%$_REQUEST[destino]%'");
}

if (isset($_REQUEST['fase']) && $_REQUEST['fase'] != "") {
    array_push($condiciones, " Estado_Alistamiento = $_REQUEST[fase]");
}

if (isset($_REQUEST['est']) && $_REQUEST['est'] != "") {
    array_push($condiciones, " Estado LIKE '%$_REQUEST[est]%'");
}

if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "") {
    $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
    $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
    array_push($condiciones, " DATE_FORMAT(Fecha, '%Y-%m-%d') BETWEEN '$fecha_inicio' AND '$fecha_fin'");
}

if (isset($_REQUEST['funcionario']) && $_REQUEST['funcionario'] != "") {

    array_push($condiciones, " Identificacion_Funcionario=$_REQUEST[funcionario]");
}
$condicion = "WHERE ".implode(' AND ', $condiciones); 

$query = 'SELECT COUNT(*) AS Total
            FROM Remision R
            LEFT JOIN Grupo_Estiba G ON G.Id_Grupo_Estiba = R.Id_Grupo_Estiba ' . $condicion;

$oCon = new consulta();

$oCon->setQuery($query);
$total = $oCon->getData();
unset($oCon);

####### PAGINACIÓN ########
$tamPag = 15;
$numReg = $total["Total"];
$paginas = ceil($numReg / $tamPag);
$limit = "";
$paginaAct = "";

if (!isset($_REQUEST['pag']) || $_REQUEST['pag'] == '') {
    $paginaAct = 1;
    $limit = 0;
} else {
    $paginaAct = $_REQUEST['pag'];
    $limit = ($paginaAct - 1) * $tamPag;
}

$query = 'SELECT R.*,
            G.Nombre AS Grupo,
            (CASE
                WHEN R.Estado_Alistamiento = 0 THEN "1"
                WHEN R.Estado_Alistamiento = 1 THEN "2"
                WHEN R.Estado_Alistamiento = 2 THEN "Listo"
            END ) as Fase,
            DATE_FORMAT(Fecha, "%d/%m/%Y") AS Fecha_Remision,
            (
            SELECT COUNT(*)
                FROM Producto_Remision PR
                WHERE PR.Id_Remision = R.Id_Remision) as Items
        FROM Remision R
        LEFT JOIN Grupo_Estiba G ON G.Id_Grupo_Estiba = R.Id_Grupo_Estiba
        ' . $condicion . ' ORDER BY Codigo DESC, Fecha DESC LIMIT ' . $limit . ',' . $tamPag;

$oCon = new consulta();

$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$remision['remisiones'] = $oCon->getData();
unset($oCon);

$remision['numReg'] = $numReg;

$i = -1;
foreach ($remision['remisiones'] as $remisiones) {
    // print_r($remision);
    /* $oItem=new complex($remisiones['Tipo_Origen'], 'Id_'.$remisiones['Tipo_Origen'], $remisiones['Id_Origen']);
    $origen= $oItem->getData();
    unset($oLista); */
    /* CAMIBOS REALIZADOS CARLOS CARDONA */

    $i++;

    $bodega = " ";
    $destino = " ";

    if ($remisiones['Tipo_Origen'] == 'Bodega') {
        $bodega = '_Nuevo';
    }

    if ($remisiones['Tipo_Destino'] == 'Bodega') {
        $destino = '_Nuevo';
    }
    $query = 'SELECT * FROM ' . $remisiones['Tipo_Origen'] . $bodega . ' WHERE Id_' . $remisiones['Tipo_Origen'] . $bodega . ' = ' . $remisiones['Id_Origen'];
    $oCon = new consulta();
    $oCon->setQuery($query);

    $origen = $oCon->getData();
    $oItem = new complex($remisiones['Tipo_Destino'] . $destino, 'Id_' . $remisiones['Tipo_Destino'] . $destino, $remisiones['Id_Destino'] . $destino);
    $destino = $oItem->getData();
    unset($oItem);

    if ($destino['Nombre_Contrato']) {
        $destino['Nombre'] = $destino['Nombre_Contrato'];
        unset($destino['Nombre_Contrato']);
    }

    $remision['remisiones'][$i]['Punto_Origen'] = $origen['Nombre'];
    $remision['remisiones'][$i]['Punto_Origen'] = $origen['Nombre'];
    $remision['remisiones'][$i]['Punto_Destino'] = $destino['Nombre'];

}

echo json_encode($remision);
