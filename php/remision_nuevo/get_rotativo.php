<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once '../../class/class.querybasedatos.php';
include_once '../../class/class.http_response.php';
include_once '../../class/class.utility.php';

$http_response = new HttpResponse();
$queryObj = new QueryBaseDatos();
$util = new Utility();

$punto_dispensacion = (isset($_REQUEST['id_destino']) ? $_REQUEST['id_destino'] : '');
$fecha_inicio = (isset($_REQUEST['fini']) ? $_REQUEST['fini'] : '');
$fecha_fin = (isset($_REQUEST['ffin']) ? $_REQUEST['ffin'] : '');
$bodega = (isset($_REQUEST['id_origen']) ? $_REQUEST['id_origen'] : '');

$id_categoria_nueva = (isset($_REQUEST['id_categoria_nueva']) ? $_REQUEST['id_categoria_nueva'] : '');
$eps = (isset($_REQUEST['eps']) ? $_REQUEST['eps'] : '');

if (strlen($eps) < 9) {
    $oItem = new complex("Eps", "Id_Eps", $eps);
    $eps_consulta = $oItem->getData();
    unset($oItem);
    $eps = $eps_consulta["Nit"];
}

$mes = isset($_REQUEST['mes']) ? $_REQUEST['mes'] : '';

$grupo = isset($_REQUEST['grupo']) ? $_REQUEST['grupo'] : '';
$grupo = (array) json_decode($grupo, true);

if ($mes > '0') {
    $hoy = date("Y-m-t", strtotime(date('Y-m-d')));
    $nuevafecha = strtotime('+' . $mes . ' months', strtotime($hoy));
    $nuevafecha = date('Y-m-d', $nuevafecha);
} else {
    $nuevafecha = date('Y-m-d');
}

$condicion = SetCondiciones();

$condicion_lotes = SetCondicionLotes();

$query = CrearQuery();
// echo $query; exit;

$queryObj->SetQuery($query);
$productos = $queryObj->ExecuteQuery('Multiple');

$j = -1;

foreach ($productos as $producto) {
    $j++;
    if ($producto["Id_Subcategoria"] != '') {
        //Busco los lotes de inventario de los productos
        $productos[$j]["Rotativo"] = $productos[$j]["Cantidad_Requerida"] . "/" . $productos[$j]['Cantidad_Inventario'];
        $productos[$j]['Cantidad_Requerida'] = ValidarRotacion($producto);

        $lotes = GetLotes($producto);

        if (count($lotes) > 0) {

            $cantidad_presentacion = $producto['Cantidad_Presentacion'];
            $cantidad = $productos[$j]['Cantidad_Requerida'];
            $modulocantidad = $cantidad % $cantidad_presentacion;
            if ($modulocantidad != 0) {
                $cantidad = $cantidad + ($cantidad_presentacion - $modulocantidad);
                $productos[$j]['Cantidad_Requerida'] = $cantidad;
            }
            $cantidad_inicial = $productos[$j]['Cantidad_Requerida'];
            $productos[$j]['Lotes'] = $lotes;

            $multiplo = 0;
            $cantidad_presentacion_producto = false;

            if ($grupo['Presentacion'] == 'Si') {
                $multiplo = $cantidad % $cantidad_presentacion;
                $cantidad_presentacion_producto = true;
            }

            $lotes_seleccionados = [];
            $lotes_visuales = [];

            if ($multiplo == 0 && $cantidad > 0) {
                $flag = true;

                for ($i = 0; $i < count($lotes); $i++) {

                    if ($flag && $cantidad <= $lotes[$i]['Cantidad']) {
                        $lote = $lotes[$i];
                        $lote['Cantidad_Seleccionada'] = $cantidad;

                        #metodo de seleccionar los lotes
                        SelecionarLotes($lote);

                        $lotes[$i]['Cantidad_Seleccionda'] = $cantidad;
                        $labellote = "Lote: " . $lotes[$i]['Lote'] . " - Vencimiento: " . $lotes[$i]['Fecha_Vencimiento'] . " - Cantidad: " . $cantidad;

                        $productos[$j]['Cantidad'] = $cantidad_inicial;

                        array_push($lotes_visuales, $labellote);
                        array_push($lotes_seleccionados, $lote);
                        $flag = false;
                    } elseif ($flag && $cantidad > $lotes[$i]['Cantidad']) {
                        $lote = $lotes[$i];
                        $lote['Cantidad_Seleccionada'] = $lotes[$i]['Cantidad'];

                        #metodo de seleccionar los lotes
                        SelecionarLotes($lote);

                        array_push($lotes_seleccionados, $lote);

                        $labellote = "Lote: " . $lotes[$i]['Lote'] . " - Vencimiento: " . $lotes[$i]['Fecha_Vencimiento'] . " - Cantidad: " . $lotes[$i]['Cantidad'];

                        $productos[$j]['Cantidad'] = $productos[$j]['Cantidad'] + $lotes[$i]['Cantidad'];

                        $cantidad = $cantidad - (int) $lotes[$i]['Cantidad'];

                        if ($cantidad_presentacion_producto) {
                            $modulo = $cantidad % $cantidad_presentacion;
                            if ($modulo != 0) {
                                $productos[$j]['Cantidad_Requerida'] = $productos[$j]['Cantidad_Requerida'] + ($cantidad_presentacion - $modulo);
                                $cantidad = $cantidad + ($cantidad_presentacion - $modulo);
                            }
                        }
                        array_push($lotes_visuales, $labellote);
                    }
                }

                $productos[$j]['Lotes_Visuales'] = $lotes_visuales;
                $productos[$j]['Lotes_Seleccionados'] = $lotes_seleccionados;
            } else {

                unset($productos[$j]);
            }
        } else {

            /*  $similares = GetSimilares($producto);
        if (!$similares) {

        unset($productos[$j]);
        } else {

        $productossimilares = GetLotesProductosimilares($similares, $producto["Cantidad"]);

        if (count($productossimilares) == 0) {

        unset($productos[$j]);
        } else {
        $productos[$j]["Similares"] = $productossimilares;
        $productos[$j]['Cantidad_Disponible'] = 0;
        }
        }*/
        }
    } else {

        unset($productos[$j]);
    }
}

foreach ($productos as $key => $producto) {

    if (!$productos[$key]['Lotes']) {
        $similares = GetSimilares($producto);
        if (!$similares) {
            /*     var_dump('no hay similares lotes',$producto ); */
            unset($productos[$key]);
        } else {

            $productossimilares = GetLotesProductosimilares($similares, $producto["Cantidad"]);

            /*   if ($producto['Id_Producto']=="46742") {
            # code...
            var_dump('si hay similares lotes',$producto );
            var_dump('si hay similares lotes',$productossimilares);
            } */
            if (count($productossimilares) == 0 || !$productossimilares) {

                unset($productos[$key]);
            } else {
                $productos[$key]["Similares"] = $productossimilares;
                $productos[$key]['Cantidad_Disponible'] = 0;
            }
        }
    }
}

$productos = array_values($productos);

sort($productos);

echo json_encode($productos);

function CrearQuery()
{

    global $condicion, $bodega, $grupo, $punto_dispensacion;
    $condicion_grupo = $grupo['Id_Grupo'] != -1 ? " AND  G.Id_Grupo_Estiba = $grupo[Id_Grupo]" : "";
    $query = 
    "     SELECT 
            r.*,B.*,  IFNULL(B.Precio, 0) AS Precio,
            IFNULL(B.Id_Producto, r.Id_Producto) AS Id_Producto,
            IFNULL(I.Cantidad_Inventario, 0) AS Cantidad_Inventario
                    FROM
                    (
                        SELECT 
                            SubC.Nombre AS Subcategoria,
                                SubC.Separable AS Categoria_Separable,
                                IFNULL((SELECT Costo_Promedio FROM Costo_Promedio WHERE Id_Producto = PRD.Id_Producto), '0') AS Costo,
                                PRD.Id_Producto,
                                PRD.Id_Subcategoria,
                                PRD.Embalaje,
                                CONCAT_WS(PRD.Principio_Activo, PRD.Presentacion, PRD.Concentracion, PRD.Cantidad, PRD.Unidad_Medida) AS Nombre,
                                PRD.Nombre_Comercial,
                                PRD.Laboratorio_Comercial,
                                PRD.Laboratorio_Generico,
                                PRD.Codigo_Cum,
                                PRD.Cantidad_Presentacion,
                                0 AS Cantidad_Pendiente,
                                (CASE
                                    WHEN PRD.Gravado = 'Si'
                                    THEN
                                        (SELECT Valor FROM Impuesto WHERE Valor > 0 ORDER BY Id_Impuesto DESC LIMIT 1)
                                    WHEN PRD.Gravado = 'No' THEN 0
                                END) AS Impuesto,
                                ROUND((SUM(PR.Cantidad_Formulada) * 1.1)) AS Cantidad_Requerida,
                                $punto_dispensacion AS Id_Punto_Dispensacion,
                                0 AS Cantidad
                        FROM
                            Producto_Dispensacion PR
                        INNER JOIN (SELECT  Id_Dispensacion, Numero_Documento, Fecha_Actual, Id_Punto_Dispensacion FROM Dispensacion WHERE Estado_Dispensacion != 'Anulada') D ON PR.Id_Dispensacion = D.Id_Dispensacion
                        INNER JOIN (SELECT  Id_Paciente, EPS, Nit FROM Paciente) PA ON D.Numero_Documento = PA.Id_Paciente 
                        INNER JOIN Producto PRD ON PR.Id_Producto = PRD.Id_Producto
                        INNER JOIN Subcategoria SubC ON PRD.Id_Subcategoria = SubC.Id_Subcategoria
                        INNER JOIN Punto_Dispensacion PTO ON PTO.Id_Punto_Dispensacion = D.Id_Punto_Dispensacion
                        $condicion
                        AND (PR.Generico IS NULL or PR.Generico != 1)
                                AND (PTO.Id_Punto_Dispensacion = $punto_dispensacion OR PTO.Id_Propharmacy = $punto_dispensacion)
                        GROUP BY PR.Id_Producto
                        HAVING Cantidad_Requerida > 0
                        ORDER BY Nombre_Comercial
                    ) r
                        INNER JOIN
                    (
                        SELECT 
                            I.Id_Producto,
                            IFNULL((SELECT  Costo_Promedio FROM Costo_Promedio WHERE Id_Producto = I.Id_Producto), '0') AS Precio,
                            SUM(I.Cantidad - (I.Cantidad_Apartada + I.Cantidad_Seleccionada)) AS Cantidad_Disponible
                        FROM
                            Inventario_Nuevo I
                        INNER JOIN Estiba E ON I.Id_Estiba = E.Id_Estiba
                        INNER JOIN Bodega_Nuevo B ON E.Id_Bodega_Nuevo = B.Id_Bodega_Nuevo
                        INNER JOIN Grupo_Estiba G ON G.Id_Grupo_Estiba = E.Id_Grupo_Estiba
                        WHERE B.Id_Bodega_Nuevo =  $bodega 
                            $condicion_grupo
                            AND E.Estado = 'Disponible'
                        GROUP BY I.Id_Producto
                    )  B ON r.Id_Producto = B.Id_Producto
                        LEFT JOIN 
                    (
						 SELECT 
							SUM(I.Cantidad - (I.Cantidad_Apartada + I.Cantidad_Seleccionada)) AS Cantidad_Inventario,
                            I.Id_Producto,
                            E.Id_Punto_Dispensacion 
						FROM Inventario_Nuevo I  
						INNER JOIN  Estiba E ON E.Id_Estiba = I.Id_Estiba 
						GROUP BY I.Id_Producto, E.Id_Punto_Dispensacion
                    )I ON I.Id_Punto_Dispensacion = r.Id_Punto_Dispensacion AND I.Id_Producto = r.Id_Producto
            HAVING Cantidad_Disponible >= 0";

    return $query;
}
function CalcularModulo($presentacion, $cantidad)
{
    $modulo = $cantidad % $presentacion;
    if ($modulo != 0) {
        $cantidad = $cantidad - $modulo;
    }
    return $cantidad;
}

function SetCondiciones()
{
    global $punto_dispensacion, $fecha_fin, $fecha_inicio, $eps, $bodega, $id_categoria_nueva;

    $condicion = '';

    $condicion .= " WHERE DATE(D.Fecha_Actual) BETWEEN '$fecha_inicio' AND '$fecha_fin'  ";

    if ($eps != '') {
        $condicion .= " AND PA.Nit='$eps' ";
    }

    /* $condicion .= " AND PRD.Id_Subcategoria IN (SELECT Id_Subcategoria FROM Categoria_Nueva_Subcategoria WHERE Id_Categoria_Nueva = $id_categoria_nueva)";
     */
    return $condicion;
}

function SetCondicionLotes()
{

    global $bodega, $id_categoria_nueva, $nuevafecha, $mes, $grupo;
    /*    */
    $condicion_grupo = $grupo['Id_Grupo'] != '-1' ? " AND  G.Id_Grupo_Estiba = $grupo[Id_Grupo]" : '';
    $condicion_principal = '
    INNER JOIN Producto PRD
    On I.Id_Producto=PRD.Id_Producto


    INNER JOIN Estiba E ON I.Id_Estiba=E.Id_Estiba
    INNER JOIN Bodega_Nuevo B ON E.Id_Bodega_Nuevo = B.Id_Bodega_Nuevo
    INNER JOIN Grupo_Estiba G ON G.Id_Grupo_Estiba = E.Id_Grupo_Estiba
    INNER JOIN Subcategoria SubC ON PRD.Id_Subcategoria = SubC.Id_Subcategoria
    WHERE E.Estado = "Disponible" AND B.Id_Bodega_Nuevo = ' . $bodega . $condicion_grupo;

    if ($grupo['Fecha_Vencimiento'] == "Si" && $mes != '-1') {

        $condicion_principal .= "  AND I.Fecha_Vencimiento>='$nuevafecha' ";
    }

    return $condicion_principal;
}

function GetLotes($producto)
{
    global $queryObj, $condicion_lotes;
    $having = "  HAVING Cantidad>0 ORDER BY I.Fecha_Vencimiento ASC";

    $query1 = "SELECT I.Id_Inventario_Nuevo, I.Id_Producto,I.Lote,(I.Cantidad-(I.Cantidad_Apartada+I.Cantidad_Seleccionada)) as
    Cantidad,I.Fecha_Vencimiento,$producto[Precio] as Precio, 0 as Cantidad_Seleccionada
    FROM Inventario_Nuevo I
    " . $condicion_lotes . " AND I.Id_Producto= $producto[Id_Producto] " . $having;

    $queryObj->SetQuery($query1);
    $lotes = $queryObj->ExecuteQuery('Multiple');

    return $lotes;
}

function ValidarRotacion($producto)
{
    $cantidad = $producto['Cantidad_Requerida'] - $producto['Cantidad_Inventario'];

    if ($cantidad < 0) {
        $cantidad = 0;
    }

    return $cantidad;
}

function GetSimilares($producto)
{

    global $queryObj;

    $query = "SELECT Producto_Asociado FROM Producto_Asociado
    WHERE (Producto_Asociado LIKE '" . $producto['Id_Producto'] . ',' . "%' OR Producto_Asociado
     LIKE '%, " . $producto['Id_Producto'] . ',' . "%' OR Producto_Asociado LIKE '%, " . $producto['Id_Producto'] . "') ";

    $queryObj->SetQuery($query);
    $productos = $queryObj->ExecuteQuery('simple');

    return $productos;
}

function GetLotesProductosimilares($productos)
{

    global $bodega, $condicion_lotes, $queryObj;

    $query = 'SELECT SUM(I.Cantidad-(I.Cantidad_Apartada+I.Cantidad_Seleccionada)) as Cantidad_Disponible,PRD.Nombre_Comercial,
    CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," ", PRD.Cantidad," ", PRD.Unidad_Medida) as Nombre, PRD.Id_Producto,
     0 as Seleccionado
    FROM Inventario_Nuevo I
    ' . $condicion_lotes . ' AND  I.Id_Producto
     IN (' . $productos['Producto_Asociado'] . ')
    GROUP BY I.Id_Producto
    HAVING Cantidad_Disponible > 0 ';

    $queryObj->SetQuery($query);
    $productos = $queryObj->ExecuteQuery('Multiple');

    return $productos;
}

function SelecionarLotes($lote)
{

    global $queryObj;

    $query = "SELECT Cantidad_Seleccionada FROM Inventario_Nuevo WHERE Id_Inventario_Nuevo =$lote[Id_Inventario_Nuevo]";
    $queryObj->SetQuery($query);
    $cantidad_seleccionada_inventario = $queryObj->ExecuteQuery('simple');
    $cantidad_total = $lote['Cantidad_Seleccionada'] + $cantidad_seleccionada_inventario['Cantidad_Seleccionada'];

    $oItem = new complex("Inventario_Nuevo", "Id_Inventario_Nuevo", $lote['Id_Inventario_Nuevo']);
    $oItem->Cantidad_Seleccionada = number_format($cantidad_total, 0, "", "");
    $oItem->save();
    unset($oItem);
}

