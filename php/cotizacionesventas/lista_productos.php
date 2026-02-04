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
    $condicion .= "AND (P.Principio_Activo LIKE '%$_REQUEST[nom]%' OR P.Presentacion LIKE '%$_REQUEST[nom]%' OR P.Concentracion LIKE '%$_REQUEST[nom]%' OR P.Nombre_Comercial LIKE '%$_REQUEST[nom]%' OR P.Cantidad LIKE '%$_REQUEST[nom]%' OR P.Unidad_Medida LIKE '%$_REQUEST[nom]%')";
}
if (isset($_REQUEST['Lista_Ganancia']) && $_REQUEST['Lista_Ganancia'] != ''){
    $condicion .= " AND PLG.Id_Lista_Ganancia=$_REQUEST[Lista_Ganancia]";
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

$query = "SELECT  P.Nombre_Comercial,
          CONCAT_WS(' ', P.Principio_Activo, P.Concentracion, '-', P.Cantidad, P.Unidad_Medida ) as Nombre,
            P.Id_Producto,
            P.Codigo_Cum as Cum,
            P.Codigo_Cum as Codigo_CUM,
            P.Invima,
            P.Fecha_Vencimiento_Invima AS Fecha_Vencimiento,
            P.Laboratorio_Comercial,
            P.Laboratorio_Generico,
            P.Embalaje,
            P.Invima,
            P.Cantidad_Presentacion,
            P.Cantidad_Presentacion as Presentacion,
            P.Gravado,
            P.Imagen,
            I.Fecha_Vencimiento as Vencimiento,
            I.Lote as Lote,
            I.Id_Inventario_Nuevo,
            P.Codigo_Cum,
            if( ifnull(PRG.Precio_Venta, 0) >0 and ifnull(PRG.Precio_Venta, 0)  < PLG.Precio, ifnull(PRG.Precio_Venta, 0) , CAST(PLG.Precio AS DECIMAL(16,2))) as Precio_Venta,
            ifnull(PRG.Precio_Venta, -1) as Precio_Regulado,
            ifnull(PLG.Precio, -1) as Precio_Lista,
            SUM(I.Cantidad) as Cantidad, 
            0 as Descuento
        FROM Producto P 
        LEFT JOIN (SELECT I.* FROM Inventario_Nuevo I INNER JOIN Estiba E on E.Id_Estiba = I.Id_Estiba Where E.Id_Bodega_Nuevo in (1, 2) )I ON P.Id_Producto=I.Id_Producto 
        LEFT JOIN Producto_Lista_Ganancia PLG ON P.Codigo_Cum=PLG.Cum 
        LEFT JOIN Precio_Regulado PRG on PRG.Codigo_Cum = P.Codigo_Cum

        WHERE P.Estado = 'Activo'
        $condicion  GROUP BY P.Id_Producto";


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
