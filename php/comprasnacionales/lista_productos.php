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
    $condicion .= ' AND (P.Principio_Activo LIKE "%'.$_REQUEST['nom'].'%" OR P.Presentacion LIKE "%'.$_REQUEST['nom'].'%" OR P.Concentracion LIKE "%'.$_REQUEST['nom'].'%" OR P.Nombre_Comercial LIKE "%'.$_REQUEST['nom'].'%" OR P.Cantidad LIKE "%'.$_REQUEST['nom'].'%" OR P.Unidad_Medida LIKE "%'.$_REQUEST['nom'].'%")';
}
if (isset($_REQUEST['lab_com']) && $_REQUEST['lab_com']) {
    $condicion .= " AND P.Laboratorio_Comercial LIKE '%$_REQUEST[lab_com]%'";
}
if (isset($_REQUEST['lab_gen']) && $_REQUEST['lab_gen']) {
    $condicion .= " AND P.Laboratorio_Generico LIKE '%$_REQUEST[lab_gen]%'";
}
if (isset($_REQUEST['cum']) && $_REQUEST['cum']) {
    $condicion .= " AND P.Codigo_Cum LIKE '%$_REQUEST[cum]%'";
}

$query = 'SELECT P.Nombre_Comercial,
        P.Codigo_Cum,
        P.Codigo_Cum as Cum,
        IFNULL(PRG.Precio_Venta, -1) as Precio_Regulado,
        CONCAT(P.Nombre_comercial," - ", CONCAT_WS(" ", IFNULL(P.Principio_Activo, P.Nombre_Comercial), P.Presentacion, P.Cantidad, P.Unidad_Medida)) as Nombre,
        P.Nombre_Comercial,
        P.Laboratorio_Comercial,
        P.Laboratorio_Generico,
        P.Id_Producto, 
        Concat_Ws("\n", P.Embalaje,"(Inventario:", IFnull( I.Disponible, 0), ")") as Embalaje,
        P.Cantidad_Presentacion, 
        P.Cantidad_Presentacion as Presentacion, 
        IFNULL(CP.Costo_Promedio,"0") AS Costo,
        IFNULL(CP.Costo_Promedio,"0") AS Costo_Promedio
        FROM Producto P 
        LEft Join Costo_Promedio CP ON CP.Id_Producto = P.Id_Producto
        LEFT JOIN Precio_Regulado PRG ON P.Codigo_Cum = PRG.Codigo_Cum
        LEFT JOIN (
            SELECT 
            SUM(I.Cantidad - I.Cantidad_Seleccionada - I.Cantidad_Apartada) as Disponible, 
            I.Id_Producto
            From Inventario_Nuevo I 
            Inner Join Estiba E on E.Id_Estiba = I.Id_Estiba 
            Where E.Id_Bodega_Nuevo = 1
            group by I.Id_Producto
        ) I on I.Id_Producto = P.Id_Producto
        WHERE P.Codigo_Barras IS NOT NULL 
        AND P.Estado="Activo" AND P.Codigo_Barras !="" 
        AND (P.Embalaje NOT LIKE "MUESTRA MEDICA%" OR P.Embalaje IS NULL OR P.Embalaje="" )'.$condicion .' GROUP BY P.Id_Producto';

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