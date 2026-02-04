<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condicion = '';

if (isset($_REQUEST['nom']) && $_REQUEST['nom'] != '') {
    $condicion .= 'WHERE (P.Principio_Activo LIKE "%'.$_REQUEST['nom'].'%" OR P.Presentacion LIKE "%'.$_REQUEST['nom'].'%" OR P.Concentracion LIKE "%'.$_REQUEST['nom'].'%" OR P.Nombre_Comercial LIKE "%'.$_REQUEST['nom'].'%" OR P.Cantidad LIKE "%'.$_REQUEST['nom'].'%" OR P.Unidad_Medida LIKE "%'.$_REQUEST['nom'].'%")';
}
if (isset($_REQUEST['lab_com']) && $_REQUEST['lab_com']) {
    $condicion .= " AND P.Laboratorio_Comercial LIKE '%$_REQUEST[lab_com]%'";
}
if (isset($_REQUEST['lab_gen']) && $_REQUEST['lab_gen']) {
    $condicion .= " AND P.Laboratorio_Generico LIKE '%$_REQUEST[lab_gen]%'";
}

$query = 'SELECT P.Nombre_Comercial,
          IF(CONCAT( P.Principio_Activo, " ",
                  P.Presentacion, " ",
                  P.Concentracion, " (", P.Nombre_Comercial,") ",
                  P.Cantidad," ",
                  P.Unidad_Medida, " LAB-", P.Laboratorio_Comercial )="" OR CONCAT( P.Principio_Activo, " ",
                  P.Presentacion, " ",
                  P.Concentracion, " (", P.Nombre_Comercial,") ",
                  P.Cantidad," ",
                  P.Unidad_Medida, " LAB-", P.Laboratorio_Comercial ) IS NULL, CONCAT(P.Nombre_Comercial," LAB-", P.Laboratorio_Comercial), CONCAT( P.Principio_Activo, " ",
                  P.Presentacion, " ",
                  P.Concentracion, " (", P.Nombre_Comercial,") ",
                  P.Cantidad," ",
                  P.Unidad_Medida, " LAB-", P.Laboratorio_Comercial )) as Nombre, P.Nombre_Comercial, P.Laboratorio_Comercial, P.Id_Producto, P.Embalaje, IFNULL(P.Laboratorio_Generico, "No aplica") AS Laboratorio_Generico, P.Gravado FROM Producto P '.$condicion .' GROUP BY P.Id_Producto';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultados = $oCon->getData();
unset($oCon);

$i=-1;
foreach($resultados as $resultado){$i++;
        $resultados[$i]["Producto"] = $resultado;
}

echo json_encode($resultados);

?>