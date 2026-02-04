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
    $condicion .= ' AND (T2.Principio_Activo LIKE "%'.$_REQUEST['nom'].'%" OR T2.Presentacion LIKE "%'.$_REQUEST['nom'].'%" OR T2.Concentracion LIKE "%'.$_REQUEST['nom'].'%" OR T2.Nombre_Comercial LIKE "%'.$_REQUEST['nom'].'%" OR T2.Cantidad LIKE "%'.$_REQUEST['nom'].'%" OR T2.Unidad_Medida LIKE "%'.$_REQUEST['nom'].'%")';
}

if (isset($_REQUEST['lab']) && $_REQUEST['lab'] != '') {
    $condicion .= ' AND Laboratorio_Generico LIKE "'.$_REQUEST['lab'].'%"';
}

if (isset($_REQUEST['lab_gral']) && $_REQUEST['lab_gral']) {
$condicion .= " AND Laboratorio_Comercial LIKE '%$_REQUEST[lab_gral]%'";
}
if (isset($_REQUEST['cum']) && $_REQUEST['cum']) {
$condicion .= " AND T2.Codigo_Cum LIKE '%$_REQUEST[cum]%'";
}

try{
    $query = 'SELECT 
                CONCAT_WS(" " ,T2.Principio_Activo,
                        T2.Presentacion,
                        T2.Concentracion,
                        T2.Cantidad,
                        T2.Unidad_Medida
                        ) as NombreProducto,
                T2.Embalaje,
                T2.Codigo_Cum,
                T2.Laboratorio_Generico as Generico, 
                T2.Laboratorio_Comercial as Comercial,
                T2.Id_Producto,
                T2.Nombre_Comercial
            FROM Inventario_Nuevo T1
            INNER JOIN Producto T2 ON T1.Id_Producto = T2.Id_Producto
            INNER JOIN Estiba E on T1.Id_Estiba = E.Id_Estiba
            WHERE
                E.Id_Estiba is not null
                -- E.Id_Punto_Dispensacion <> 0 AND
                -- E.Id_Bodega_Nuevo != 0 
               '.$condicion.'  GROUP BY T1.Id_Producto';
        

    $oCon= new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $productos = $oCon->getData();
    unset($oCon);

    echo json_encode($productos);
}catch(Exception $e){

}
?>