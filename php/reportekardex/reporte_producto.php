<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condicion = '';

$tipo = $_REQUEST['tipo'];

if (isset($_REQUEST['nom']) && $_REQUEST['nom'] != '') {
    $condicion .= ' AND (P.Principio_Activo LIKE "%'.$_REQUEST['nom'].'%" OR P.Presentacion LIKE "%'.$_REQUEST['nom'].'%" OR P.Concentracion LIKE "%'.$_REQUEST['nom'].'%" OR P.Nombre_Comercial LIKE "%'.$_REQUEST['nom'].'%" OR P.Cantidad LIKE "%'.$_REQUEST['nom'].'%" OR P.Unidad_Medida LIKE "%'.$_REQUEST['nom'].'%")';
}
if (isset($_REQUEST['lote']) && $_REQUEST['lote']) {
    $condicion .= " AND I.Lote LIKE '%$_REQUEST[lote]%'";
}
if (isset($_REQUEST['lab_com']) && $_REQUEST['lab_com']) {
    $condicion .= " AND P.Laboratorio_Comercial LIKE '%$_REQUEST[lab_com]%'";
}
if (isset($_REQUEST['lab_gen']) && $_REQUEST['lab_gen']) {
    $condicion .= " AND P.Laboratorio_Generico LIKE '%$_REQUEST[lab_gen]%'";
}

if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "") {
    $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
    $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
    $condicion .= " AND R.Fecha BETWEEN '$fecha_inicio' AND '$fecha_fin'";
}

$query  = '';

if ($tipo == "Bodega") {
    $query = 'SELECT P.Nombre_Comercial,
          CONCAT( P.Principio_Activo, " ",
                  P.Presentacion, " ",
                  P.Concentracion,
                  P.Cantidad," ",
                  P.Unidad_Medida, " LAB-", P.Laboratorio_Comercial ) as Nombre_Producto, I.Lote, I.Fecha_Vencimiento, "Remision" AS Tipo, I.Cantidad AS Cantidad_Disponible, PR.Cantidad, R.Codigo AS Codigo_Remision, R.Id_Remision FROM Producto P INNER JOIN Inventario I ON P.Id_Producto=I.Id_Producto INNER JOIN Producto_Remision PR ON I.Id_Inventario=PR.Id_Inventario INNER JOIN Remision R ON PR.Id_Remision=R.Id_Remision WHERE I.Id_Bodega='.$_REQUEST["Id"].' '.$condicion .'';
}

/* echo $query;
exit; */

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultados = $oCon->getData();
unset($oCon);


echo json_encode($resultados);

?>