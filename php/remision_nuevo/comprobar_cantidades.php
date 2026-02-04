<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

date_default_timezone_set('America/Bogota');

//require_once('../../config/start.inc.php');
include_once '../../class/class.querybasedatos.php';
include_once '../../class/class.http_response.php';
include_once '../../class/class.utility.php';

$http_response = new HttpResponse();
$queryObj = new QueryBaseDatos();
$util = new Utility();

$id_origen = (isset($_REQUEST['id_origen']) ? $_REQUEST['id_origen'] : '');

$id_producto = (isset($_REQUEST['id_producto']) ? $_REQUEST['id_producto'] : '');
$id_destino = (isset($_REQUEST['id_destino']) ? $_REQUEST['id_destino'] : '');
$tipo = (isset($_REQUEST['tipo_origen']) ? $_REQUEST['tipo_origen'] : '');
$mes = isset($_REQUEST['meses']) ? $_REQUEST['meses'] : '';
$id_categoria_nueva = isset($_REQUEST['id_categoria_nueva']) ? $_REQUEST['id_categoria_nueva'] : '';

// echo $id_destino;
$tipo_destino = isset($_REQUEST['tipo_destino']) ? $_REQUEST['tipo_destino'] : '';

$grupo = isset($_REQUEST['grupo']) ? $_REQUEST['grupo'] : '';

$grupo = (array) json_decode($grupo, true);

if ($mes > '0') {
    $hoy = date("Y-m-t", strtotime(date('Y-m-d')));
    $nuevafecha = strtotime('+' . $mes . ' months', strtotime($hoy));
    $nuevafecha = date('Y-m-d', $nuevafecha);
} else {
    $nuevafecha = date('Y-m-d');
}

$campos = "";

$condicion_principal = SetCondiciones();

$query = GetQuery();
// echo $query; exit;
$queryObj->SetQuery($query);
$productos = $queryObj->ExecuteQuery('Multiple');
$productos = GetLotes($productos);

echo json_encode($productos);

function SetCondiciones()
{
    global $nuevafecha, $tipo, $id_origen, $id_producto, $id_categoria_nueva, $tipo_destino, $grupo, $mes, $campos;

    $condicion_principal = '';

    if ($tipo == 'Bodega') {
        // $condicion_1=  $grupo['Id_Grupo'] =='0' ? " AND  G.Nombre  not like '%POSITIVA%' ":
        $condicion_grupo= ($grupo['Id_Grupo']!='-1' && $grupo['Id_Grupo'] > '0' ? " AND  G.Id_Grupo_Estiba = $grupo[Id_Grupo]":'');
        
        // echo $condicion_grupo; exit;
        
        $campos = " G.Id_Grupo_Estiba, G.Nombre as Grupo,";
   
        $condicion_principal .= 'INNER JOIN Estiba E ON I.Id_Estiba=E.Id_Estiba
        INNER JOIN Bodega_Nuevo B ON E.Id_Bodega_Nuevo = B.Id_Bodega_Nuevo
        INNER JOIN Grupo_Estiba G ON G.Id_Grupo_Estiba = E.Id_Grupo_Estiba
        WHERE E.Estado = "Disponible" AND B.Id_Bodega_Nuevo = ' . $id_origen . $condicion_grupo;

        if ($tipo_destino != 'Bodega' && ( ($grupo['Fecha_Vencimiento'] == 'Si' || $grupo['Id_Grupo']=='0' )&& $mes != '-1')) {
            $condicion_principal .= "  AND I.Fecha_Vencimiento>='$nuevafecha' ";
        }
    } else if ($tipo == 'Punto_Dispensacion') {
        $campos = " '' as Id_Grupo_Estiba, '' as  Grupo,";
        $condicion_principal .= "
        INNER JOIN Estiba E ON I.Id_Estiba=E.Id_Estiba
        WHERE E.Estado = 'Disponible' AND E.Id_Punto_Dispensacion=$id_origen ";
    }

    if ($condicion_principal == '') {
        $condicion_principal .= " WHERE I.Id_Producto=$id_producto";
    } else {
        $condicion_principal .= " AND I.Id_Producto=$id_producto";
    }

    return $condicion_principal;
}

function GetQuery()
{
    global $condicion_principal, $tipo_destino, $id_destino;
    $condicionContrato = " AND IC.Id_Contrato = '$id_destino'";
    if ($tipo_destino === 'Contrato') {
        $query = 'SELECT SubC.Nombre as Subcategoria,
                    PRD.Id_Subcategoria,
                    SUM(IC.Cantidad - (IC.Cantidad_Apartada+IC.Cantidad_Seleccionada)) as Cantidad_Disponible
                    FROM Inventario_Contrato IC
                    INNER JOIN Inventario_Nuevo I ON I.Id_Inventario_Nuevo = IC.Id_Inventario_Nuevo
                    INNER JOIN Producto PRD
                    ON I.Id_Producto=PRD.Id_Producto
                    INNER JOIN Subcategoria SubC ON PRD.Id_Subcategoria = SubC.Id_Subcategoria
                    ' . $condicion_principal . $condicionContrato;
    } else {
        $inventarioContratoJoin = 'LEFT JOIN (
                SELECT Id_Inventario_Nuevo,
                       SUM(Cantidad) AS Cantidad,
                       SUM(Cantidad_Apartada) AS Cantidad_Apartada,
                       SUM(Cantidad_Seleccionada) AS Cantidad_Seleccionada
                FROM Inventario_Contrato
                GROUP BY Id_Inventario_Nuevo
            ) IC ON I.Id_Inventario_Nuevo = IC.Id_Inventario_Nuevo';
        $query = 'SELECT SubC.Nombre as Subcategoria,
                    PRD.Id_Subcategoria,
                    SUM(I.Cantidad-(I.Cantidad_Apartada+I.Cantidad_Seleccionada)-IFNULL(IC.Cantidad,0)) as Cantidad_Disponible
                    FROM Inventario_Nuevo I
                    ' . $inventarioContratoJoin . '
                    INNER JOIN Producto PRD On I.Id_Producto=PRD.Id_Producto
                    INNER JOIN Subcategoria SubC ON PRD.Id_Subcategoria = SubC.Id_Subcategoria
                    ' . $condicion_principal;
    }
    return $query;
}

function GetLotes($productos)
{
    global $queryObj, $condicion_principal, $tipo, $tipo_destino, $campos;

    $resultado = [];
    $having = "  HAVING Cantidad>0 ORDER BY I.Fecha_Vencimiento ASC";
    $i = -1;
    $pos = 0;
    $condicionBodega = '';
    $inventarioContratoJoin = 'LEFT JOIN (
            SELECT Id_Inventario_Nuevo,
                   SUM(Cantidad) AS Cantidad
            FROM Inventario_Contrato
            GROUP BY Id_Inventario_Nuevo
        ) IC ON I.Id_Inventario_Nuevo = IC.Id_Inventario_Nuevo';
    if ($tipo == 'Bodega') {
        $condicionBodega .= '
        INNER JOIN Producto PRD
        On I.Id_Producto=PRD.Id_Producto
        INNER JOIN Subcategoria SubC ON PRD.Id_Subcategoria = SubC.Id_Subcategoria ';
    }

    foreach ($productos as $value) {
        $i++;

        if ($tipo_destino === 'Contrato') {
            $query1 = "SELECT I.Id_Inventario_Nuevo, I.Id_Producto,I.Lote,IC.Id_Inventario_Contrato, I.Fecha_Vencimiento,
            
                            $campos
                             (IC.Cantidad-(IC.Cantidad_Apartada+IC.Cantidad_Seleccionada)) as Cantidad, 0 as Cantidad_Seleccionada
                       FROM Inventario_Contrato IC
                       INNER JOIN Inventario_Nuevo I ON I.Id_Inventario_Nuevo = IC.Id_Inventario_Nuevo
                       " . $condicionBodega . $condicion_principal . $having;
        } else {
            $query1 = "SELECT I.Id_Inventario_Nuevo, I.Id_Producto,I.Lote,
                            $campos
                              (I.Cantidad-(I.Cantidad_Apartada+I.Cantidad_Seleccionada)-IFNULL(IC.Cantidad,0)) as Cantidad,
                              I.Fecha_Vencimiento,0 as Cantidad_Seleccionada FROM Inventario_Nuevo I
                              $inventarioContratoJoin
                              $condicionBodega  $condicion_principal
                               $having";
        }
        $queryObj->SetQuery($query1);
        $lotes = $queryObj->ExecuteQuery('Multiple');

        if (count($lotes) > 0) {
            $resultado[$pos] = $productos[$i];
            $resultado[$pos]['Lotes'] = $lotes;
            $pos++;
        } else {
            unset($productos[$i]);
        }
    }

    return $resultado;
}


