<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
$id_bodega = (isset($_REQUEST['id_bodega_nuevo']) ? (int)$_REQUEST['id_bodega_nuevo'] : 0);
$id_acta_recepcion = (isset($_REQUEST['id_acta']) && is_numeric($_REQUEST['id_acta']))
    ? (int)$_REQUEST['id_acta']
    : 0;

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

$condicion_acta = '';
if ($id_acta_recepcion > 0) {
    $condicion_acta = ' AND PR.Id_Acta_Recepcion = ' . $id_acta_recepcion . ' ';
}

$query =
'SELECT P.Nombre_Comercial,
    IF(
        CONCAT( P.Nombre_Comercial," ",P.Cantidad, " ",P.Unidad_Medida, " (",P.Principio_Activo, " ", P.Presentacion, " ", P.Concentracion, ") " )="" 
        OR CONCAT( P.Nombre_Comercial," ", P.Cantidad," ", P.Unidad_Medida ," (",P.Principio_Activo, " ", P.Presentacion, " ", P.Concentracion, ") " ) IS NULL, 
        CONCAT(P.Nombre_Comercial), CONCAT( P.Nombre_Comercial," ", P.Cantidad," ", P.Unidad_Medida, " (",P.Principio_Activo, " ", P.Presentacion, " ", P.Concentracion,") " )) as Nombre,
    IF(
        CONCAT( P.Nombre_Comercial," ",P.Cantidad, " ",P.Unidad_Medida, " (",P.Principio_Activo, " ", P.Presentacion, " ", P.Concentracion, ") " )="" 
        OR CONCAT( P.Nombre_Comercial," ", P.Cantidad," ", P.Unidad_Medida ," (",P.Principio_Activo, " ", P.Presentacion, " ", P.Concentracion, ") " ) IS NULL, 
        CONCAT(P.Nombre_Comercial), CONCAT( P.Nombre_Comercial," ", P.Cantidad," ", P.Unidad_Medida, " (",P.Principio_Activo, " ", P.Presentacion, " ", P.Concentracion,") " )) as producto,
    P.Nombre_Comercial,
    P.Laboratorio_Comercial,
    P.Id_Producto,
    P.Embalaje,
    P.Cantidad_Presentacion, 
    IFNULL(P.Laboratorio_Generico, "No aplica")
    AS Laboratorio_Generico, 
    P.Gravado,
    IF(P.Gravado ="Si", (SELECT Valor from Impuesto Order By Id_Impuesto Desc Limit 1), 0) as Impuesto,
    P.Codigo_Cum,
    P.Imagen,
    I.Lote,
    I.Fecha_Vencimiento, I.Id_Inventario_Nuevo, 
        (I.Cantidad-I.Cantidad_Seleccionada-I.Cantidad_Apartada) as Cantidad_Inventario,
        IFNULL(PR.Precio,0) AS Costo,
        E.Nombre AS Nombre_Estiba, 
        (SELECT G.Nombre From Grupo_Estiba G WHERE G.Id_Grupo_Estiba =  E.Id_Grupo_Estiba) AS Grupo_Estiba
         FROM Inventario_Nuevo I         
          INNER JOIN Producto P ON P.Id_Producto=I.Id_Producto 
          INNER JOIN Estiba E ON E.Id_Estiba = I.Id_Estiba
		  INNER JOIN Producto_Acta_Recepcion PR ON P.Id_Producto=PR.Id_Producto and I.Lote = PR.Lote
          WHERE P.Codigo_Barras IS NOT NULL AND P.Codigo_Barras !=""    ' . $condicion_acta . '
          AND (I.Cantidad - (I.Cantidad_Seleccionada + I.Cantidad_Apartada) ) >0  AND E.Id_Bodega_Nuevo='.$id_bodega.$condicion .' ORDER BY I.Id_Producto, I.Lote';

//var_dump($query); exit;
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
