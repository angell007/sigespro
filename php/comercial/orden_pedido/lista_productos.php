<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');

$condicion = '';
$cliente = isset($_REQUEST['cliente']) && $_REQUEST['cliente'] != '' ? $_REQUEST['cliente'] : false;

if (!$cliente) {
    echo json_encode(['mensaje'=>'debe enviar el cliente']);exit;
}

if (isset($_REQUEST['nom']) && $_REQUEST['nom'] != '') {
    $condicion .= ' AND (P.Principio_Activo LIKE "%'.$_REQUEST['nom'].'%" OR P.Presentacion LIKE "%'.$_REQUEST['nom'].'%" OR P.Concentracion LIKE "%'.$_REQUEST['nom'].'%" OR P.Nombre_Comercial LIKE "%'.$_REQUEST['nom'].'%" OR P.Cantidad LIKE "%'.$_REQUEST['nom'].'%" OR P.Unidad_Medida LIKE "%'.$_REQUEST['nom'].'%")';
}
if (isset($_REQUEST['lab_com']) && $_REQUEST['lab_com']) {
    $condicion .= " AND P.Laboratorio_Comercial LIKE '%$_REQUEST[lab_com]%'";
}
if (isset($_REQUEST['subcategoria']) && $_REQUEST['subcategoria']) {
    $condicion .= " AND S.Id_Subcategoria = '$_REQUEST[subcategoria]'";
}
if (isset($_REQUEST['lab_gen']) && $_REQUEST['lab_gen']) {
    $condicion .= " AND P.Laboratorio_Generico LIKE '%$_REQUEST[lab_gen]%'";
}
if (isset($_REQUEST['cum']) && $_REQUEST['cum']) {
    $condicion .= " AND P.Codigo_Cum LIKE '%$_REQUEST[cum]%'";
}

$query = ' SELECT Id_Lista_Ganancia , Id_Cliente FROM  Cliente WHERE Id_Cliente = '.$cliente;
$oCon= new consulta();
$oCon->setQuery($query);
$cliente = $oCon->getData();
unset($oCon);




$query = "SELECT P.Nombre_Comercial, P.Codigo_Cum ,
P.Imagen,
S.Nombre AS Subcategoria,
IF(CONCAT( P.Nombre_Comercial,' ',P.Cantidad, ' ',P.Unidad_Medida, ' (',P.Principio_Activo, ' ',
        P.Presentacion, ' ',
        P.Concentracion, ') ' )='' OR CONCAT( P.Nombre_Comercial,' ', P.Cantidad,' ',
        P.Unidad_Medida ,' (',P.Principio_Activo, ' ',
        P.Presentacion, ' ',
        P.Concentracion, ') '
       ) IS NULL, CONCAT(P.Nombre_Comercial), CONCAT( P.Nombre_Comercial,' ', P.Cantidad,' ',
        P.Unidad_Medida, ' (',P.Principio_Activo, ' ',
        P.Presentacion, ' ',
        P.Concentracion,') ' )) as Nombre, 
P.Nombre_Comercial, P.Laboratorio_Comercial, P.Laboratorio_Generico, P.Id_Producto, 
 Concat( '(', ifnull(I.Disponible, 0), ')\n'  , P.Embalaje) as Embalaje, P.Cantidad_Presentacion, 

    if(PLG.Precio > PRG.Precio_Venta, PRG.Precio_Venta, PLG.Precio   ) AS Precio, 
    ( PLG.Precio) AS Precio_Lista, 
    IFNULL(PRG.Precio_Venta, 0 ) as Precio_Regulado,
            '' AS Precio_Orden,

IFNULL((SELECT Costo_Promedio  FROM Costo_Promedio WHERE Id_Producto = P.Id_Producto),'0') AS Costo,


IF(P.Gravado = 'Si' , (select Valor From Impuesto order By Id_Impuesto desc limit 1) , 0 ) AS Impuesto,
0 AS Total,
'' AS Cantidad,
P.Cantidad_Presentacion,
0 as Descuento,
0 AS Cantidad_Remision

FROM Producto P
INNER JOIN Subcategoria S ON S.Id_Subcategoria = P.Id_Subcategoria
Left Join Precio_Regulado PRG on PRG.Codigo_Cum = P.Codigo_Cum
Inner Join (   SELECT Precio, Cum FROM Producto_Lista_Ganancia PLG INNER JOIN Lista_Ganancia LG ON LG.Id_Lista_Ganancia = PLG.Id_Lista_Ganancia  WHERE LG.Id_Lista_Ganancia = '$cliente[Id_Lista_Ganancia]' )PLG on PLG.Cum = P.Codigo_Cum
LEFT JOIN (SELECT I.*, SUM(I.Cantidad- I.Cantidad_Apartada-I.Cantidad_Seleccionada) as Disponible FROM Inventario_Nuevo I INNER JOIN Estiba E on E.Id_Estiba = I.Id_Estiba Where E.Id_Bodega_Nuevo in (1, 2) Group By I.Id_Producto)I ON P.Id_Producto=I.Id_Producto 
        

WHERE
P.Codigo_Barras IS NOT NULL AND P.Estado='Activo' AND P.Codigo_Barras !='' AND
(P.Embalaje NOT LIKE 'MUESTRA MEDICA%' OR P.Embalaje IS NULL OR P.Embalaje='' ) $condicion 
GROUP BY P.Id_Producto
HAVING Precio 
        ";
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultados = $oCon->getData();
unset($oCon);

/* 
INNER JOIN Estiba E ON I.Id_Estiba=E.Id_Estiba
            INNER JOIN Bodega_Nuevo B ON E.Id_Bodega_Nuevo = B.Id_Bodega_Nuevo
            INNER JOIN Grupo_Estiba G ON G.Id_Grupo_Estiba = E.Id_Grupo_Estiba
            WHERE E.Estado = "Disponible" AND  B.Id_Bodega_Nuevo = ' . $id_origen . ' AND  G.Id_Grupo_Estiba = ' . $grupo['Id_Grupo']; */


echo json_encode($resultados);

?>