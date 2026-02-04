<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../../config/start.inc.php');
include_once('../../../class/class.lista.php');
include_once('../../../class/class.complex.php');
include_once('../../../class/class.consulta.php');


$condicion = '';
$id = isset($_REQUEST['id']) && $_REQUEST['id'] != '' ? $_REQUEST['id'] : false;
$id_bodega = isset($_REQUEST['bodega']) && $_REQUEST['bodega'] != '' ? $_REQUEST['bodega'] : false;
$mes = isset($_REQUEST['vencimiento']) && $_REQUEST['vencimiento'] != '' ? $_REQUEST['vencimiento'] : false;

$mes = (int)$mes;
if ($mes > 0) {
    $vencimiento = "AND Date(I.Fecha_Vencimiento) >= DATE_ADD(now(), interval $mes month)";
}

if (!$id || !$id_bodega) {
    echo json_encode(['debe ingresar el id']);
    exit;
}


$condicion_principal = "
INNER JOIN Estiba E ON I.Id_Estiba=E.Id_Estiba
INNER JOIN Bodega_Nuevo B ON E.Id_Bodega_Nuevo = B.Id_Bodega_Nuevo
INNER JOIN Grupo_Estiba G ON G.Id_Grupo_Estiba = E.Id_Grupo_Estiba
WHERE E.Estado = 'Disponible' AND  B.Id_Bodega_Nuevo = $id_bodega 
$vencimiento
";

$query = "SELECT O.*,
                CONCAT( TRIM(F.Nombres), ' ', TRIM(F.Apellidos)) as Nombre_Funcionario,
                C.Id_Lista_Ganancia,
                C.Nombre AS Nombre_Cliente
                FROM Orden_Pedido O
                INNER JOIN Funcionario F ON F.Identificacion_Funcionario = O.Identificacion_Funcionario
                INNER JOIN Cliente C ON C.Id_Cliente = O.Id_Cliente
            WHERE concat(O.Prefijo, O.Id_Orden_Pedido) = '$id'";
$oCon = new consulta();
$oCon->setQuery($query);
$cabecera = $oCon->getData();
unset($oCon);


$query = "SELECT P.Nombre_Comercial, P.Codigo_Cum,
            P.Imagen,
            S.Nombre AS Subcategoria,
            S.Separable AS Categoria_Separable,
            P.Id_Subcategoria,
            C.Nombre as Categoria,
            0 as Seleccionado,
            P.Codigo_Cum, 
            IFNULL((SELECT Costo_Promedio  FROM Costo_Promedio WHERE Id_Producto = P.Id_Producto),'0') as Costo,
            REPLACE(P.Codigo_Cum,'-', '') as Cum ,

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
            P.Nombre_Comercial,
            P.Laboratorio_Comercial,
            P.Laboratorio_Generico,
            P.Id_Producto,
            P.Embalaje,
            P.Cantidad_Presentacion, 
            (CASE WHEN PRG.Codigo_Cum IS NOT NULL THEN 'Si' WHEN PRG.Codigo_Cum IS  NULL THEN 'No' END ) as Regulado,
            ( CASE WHEN PRG.Codigo_Cum IS NOT NULL THEN PRG.Precio WHEN PRG.Codigo_Cum IS  NULL THEN 0 END ) as Precio_Regulado ,
            PO.Cantidad ,
            ( PO.Cantidad - ifnull(PR.Remisionada, 0)) as Pendiente,
            PR.Remisionada,
            IF(PO.Costo = 0, IFNULL((SELECT Costo_Promedio  FROM Costo_Promedio WHERE Id_Producto = P.Id_Producto),'0'), '0') as Costo,
            PO.Precio_Orden as Precio,
            PO.Impuesto,
            PO.Descuento,
            (  ((PO.Impuesto * (PO.Cantidad * PO.Precio_Orden))  /  100)  + ( PO.Cantidad * PO.Precio_Orden )  ) AS Total,

                IFNULL(SUM(I.Disponible), 0) AS Cantidad_Disponible,
            GROUP_CONCAT( CONCAT(I.Lotes_Disponibles, ':', I.Grupo_Estiba, ':', I.Disponible)) AS Visual



            FROM Producto_Orden_Pedido PO
            INNER JOIN Producto P ON P.Id_Producto = PO.Id_Producto
            INNER JOIN Subcategoria S ON S.Id_Subcategoria = P.Id_Subcategoria
            INNER JOIN Categoria_Nueva C on C.Id_Categoria_Nueva = S.Id_Categoria_Nueva
            LEFT JOIN (
                SELECT I.Id_Producto, 
                    SUM(I.Cantidad - I.Cantidad_Apartada - I.Cantidad_Seleccionada) AS Disponible, 
                    G.Nombre AS Grupo_Estiba,
                    GROUP_CONCAT( DISTINCT I.Lote SEPARATOR '|') AS Lotes_Disponibles
                    FROM Inventario_Nuevo I 
                    INNER JOIN Estiba E ON E.Id_Estiba = I.Id_Estiba
                    
                    INNER JOIN Grupo_Estiba G ON G.Id_Grupo_Estiba = E.Id_Grupo_Estiba
                    WHERE E.Id_Bodega_Nuevo ='$id_bodega'
                    $vencimiento
                    AND (I.Cantidad - I.Cantidad_Apartada - I.Cantidad_Seleccionada)>0
                    
                    GROUP BY I.Id_Producto, G.Id_Grupo_Estiba
            ) I ON I.Id_Producto = P.Id_Producto
            left JOIN (SELECT Precio, Codigo_Cum,  REPLACE(Codigo_Cum,'-','') as Cum FROM Precio_Regulado group  BY Cum ) PRG ON P.Codigo_Cum = PRG.Codigo_Cum

            Left Join 
            (       Select Sum(PR.Cantidad) as Remisionada,
                    PR.Id_Producto, R.Id_Orden_Pedido
                    FROM Producto_Remision PR 
                    Inner Join Remision R on PR.Id_Remision = R.Id_Remision
                    Inner Join Orden_Pedido OP On OP.Id_Orden_Pedido = R.Id_Orden_Pedido
                    WHERE OP.Id_Orden_Pedido = '$cabecera[Id_Orden_Pedido]'
                    And R.Estado != 'Anulada'
                    Group By OP.Id_Orden_Pedido, PR.Id_Producto
            ) PR on PR.Id_Producto = PO.Id_Producto and PR.Id_Orden_Pedido = PO.Id_Orden_Pedido

            WHERE PO.Id_Orden_Pedido ='$cabecera[Id_Orden_Pedido]'
            GROUP BY P.Id_Producto
            Having Pendiente >0";

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$productosOrden = $oCon->getData();
$productosOrden = GetLotes($productosOrden);
unset($oCon);

foreach ($productosOrden as $key => $producto) {
    if($producto['Cantidad_Disponible'] < $producto['Pendiente']){
        $similares = GetSimilares($producto);
        if($similares && $similares['Producto_Asociado']){
            $productossimilares = GetLotesProductosimilares($similares, $producto);
            $producto["Similares"] = $productossimilares;
            $producto['Cantidad_Disponible'] = '0';
        }
    }
    $productosOrden[$key]=$producto;
}



echo json_encode(['cabecera' => $cabecera, 'productosOrden' => $productosOrden]);

function GetLotes($productos)
{
    global $condicion_principal;
    $having = "  HAVING Cantidad>0 ORDER BY I.Fecha_Vencimiento ASC";
    $i = -1;
    foreach ($productos as  $value) {
        $i++;
        $query1 = "SELECT I.Id_Inventario_Nuevo, 
                G.Nombre as Grupo_Estiba,
                G.Id_Grupo_Estiba,
                I.Id_Producto,I.Lote,(I.Cantidad-(I.Cantidad_Apartada+I.Cantidad_Seleccionada)) as Cantidad,
                I.Fecha_Vencimiento,$value[Precio] as Precio, 0 as Cantidad_Seleccionada 
                FROM Inventario_Nuevo I 
                INNER JOIN Producto PRD On I.Id_Producto=PRD.Id_Producto
                INNER JOIN Subcategoria SubC ON PRD.Id_Subcategoria = SubC.Id_Subcategoria
                $condicion_principal 
                AND I.Id_Producto= $value[Id_Producto]  $having
                ";

        $queryObj = new consulta();
        $queryObj->SetQuery($query1);
        $queryObj->setTipo('Multiple');
        $lotes = $queryObj->getData();
        $productos[$i]['Lotes'] = $lotes;
        $productos[$i]['Lotes_Visuales'] = [];
    }
    return $productos;
}

function GetSimilares($producto)
{
    $queryObj = new consulta();
    $query = "SELECT 
            CONCAT_WS(',',PA.Producto_Asociado, GROUP_CONCAT(PA1.Producto_Asociado) ) as Producto_Asociado 
            FROM Producto_Asociado PA
            Left Join Producto_Asociado PA1 ON PA1.Id_Producto_Asociado = PA.Id_Asociado_Genericos
            Where PA.Asociados2 LIKE '%-$producto[Id_Producto]-%'
        ";
    $queryObj->SetQuery($query);
    $productos = $queryObj->getData();

    // echo json_encode($productos); exit;
    return $productos;


}

function GetLotesProductosimilares($productos, $producto)
{

    global $condicion_principal;
    $queryObj = new consulta();

    $query = "SELECT SUM(I.Cantidad-(I.Cantidad_Apartada+I.Cantidad_Seleccionada)) as Cantidad_Disponible,PRD.Nombre_Comercial,
    CONCAT(PRD.Principio_Activo,' ',PRD.Presentacion,' ',PRD.Concentracion,' ', PRD.Cantidad,' ', PRD.Unidad_Medida, '\n (', PRD.Codigo_Cum,')') as Nombre,
    PRD.Codigo_Cum,
     0 as Seleccionado,
     PRD.Id_Producto
    FROM  Inventario_Nuevo I
    Inner Join Producto PRD on PRD.Id_Producto = I.Id_Producto
        $condicion_principal
    AND  I.Id_Producto  IN ($productos[Producto_Asociado])
    and I.Id_Producto != '$producto[Id_Producto]'
    GROUP BY I.Id_Producto
    HAVING Cantidad_Disponible > 0 ";

    $queryObj->SetQuery($query);
    $queryObj->setTipo('Multiple');
    $productos = $queryObj->getData();

    return $productos;
}
