<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.http_response.php');
require_once('../../class/class.configuracion.php');

$queryObj = new QueryBaseDatos();
$response = array();
$http_response = new HttpResponse();

date_default_timezone_set('America/Bogota');

$modelo = (isset($_REQUEST['modelo']) ? $_REQUEST['modelo'] : '');
$modelo = (array) json_decode($modelo);
$mes = isset($_REQUEST['mes']) ? $_REQUEST['mes'] : '';


$hoy = date("Y-m-t", strtotime(date('Y-m-d')));
$nuevafecha = strtotime("+ $mes months", strtotime($hoy));
$nuevafecha = date('Y-m-t', $nuevafecha);


$nombre_antiguo = GetNombreProducto($modelo['Id_Producto_Viejo']);
$nombre_nuevo = GetNombreProducto($modelo['Id_Producto_Nuevo']);
$condicion_lotes = SetCondicionLotes();


$condicion = SetCondiciones();
$query = GetQuery();

$queryObj->SetQuery($query);
$producto = $queryObj->ExecuteQuery('simple');

// echo json_encode($producto);exit;


if (isset($producto) && $producto['Pendiente'] != '0') {
    if($producto['Pendiente'] == $producto['Ofertada']){
        $oItem = new complex("Producto_Orden_Pedido", "Id_Producto_Orden_Pedido", $producto['Id_Producto_Orden_Pedido']);
        $oItem->Id_Producto = $modelo['Id_Producto_Nuevo'];
        $oItem->save();
    }
    else{
        // $query = "UPDATE Producto_Orden_Pedido SET Cantidad = $producto[Enviada] WHERE Id_Producto_Orden_Pedido = $producto[Id_Producto_Orden_Pedido]"; 
        // $oCon = new consulta();
        // $oCon->setQuery($query);
        // $oCon->getData();

        $oItem = new complex("Producto_Orden_Pedido", "Id_Producto_Orden_Pedido", $producto['Id_Producto_Orden_Pedido']);
        $pop=$oItem->getData();
        $oItem->Cantidad = $producto['Enviada'];
        $oItem->save();
        unset($oItem);

        $oItem = new complex("Producto_Orden_Pedido", "Id_Producto_Orden_Pedido");

        foreach ($pop as $key => $value) {
            if( isset($value) && $key!='Id_Producto_Orden_Pedido'){
                $oItem->$key=$value;
            }
        }
        $oItem->Cantidad = $producto['Ofertada']-$producto['Enviada'];
        $oItem->Id_Producto = $modelo['Id_Producto_Nuevo'];
        $oItem->save();

    }
    $id_registro= RegistrarActividadCambio($producto['Id_Orden_Pedido']);
} 
unset($oItem);




$query = GetQueryProducto($modelo['Id_Producto_Nuevo']);
$queryObj->SetQuery($query);
$producto = $queryObj->ExecuteQuery('simple');

$lotes = GetLotes($producto);

if (count($lotes) > 0) {
    $cantidad_presentacion = $producto['Cantidad_Presentacion'];
    $cantidad = $modelo['Cantidad'];
    $producto['Cantidad_Requerida'] = $modelo['Cantidad'];

    $modulocantidad = $cantidad % $cantidad_presentacion;
    if ($modulocantidad != 0) {
        $cantidad = $cantidad + ($cantidad_presentacion - $modulocantidad);
        $producto['Cantidad_Requerida'] = $cantidad;
    }


    $cantidad_inicial = $producto['Cantidad_Requerida'];
    $producto['Lotes'] = $lotes;


    $multiplo = 0;
    $cantidad_presentacion_producto = false;

    if ($grupo['Presentacion'] == 'Si') {
        $multiplo = $cantidad % $cantidad_presentacion;
        $cantidad_presentacion_producto = true;
    }

    $lotes_seleccionados = [];
    $lotes_visuales = [];

    $producto['Lotes_Visuales'] = $lotes_visuales;
    $producto['Lotes_Seleccionados'] = $lotes_seleccionados;
}
$http_response->SetRespuesta(0, 'Operacion Exitosa', 'Se ha guardado cambiado correctamente el cambio de producto!');
$response = $http_response->GetRespuesta();

$response['producto'] = $producto;
$response['id_cambio'] = $id_registro;

echo json_encode($response);

function SetCondiciones()
{
    global $modelo;

    $condicion = '';

    $condicion .= " WHERE POP.Id_Orden_Pedido = $modelo[Id_Orden_Pedido] 
        AND POP.Id_Producto=$modelo[Id_Producto_Viejo] ";

    return $condicion;
}

function GetQuery()
{
    global $condicion, $modelo;

    $query = "SELECT
          POP.Id_Producto_Orden_Pedido, 
          POP.Id_Producto, 
          POP.Id_Orden_Pedido, 
          POP.Cantidad as Ofertada,
          IFNULL(PR.Remisionada, 0) as Enviada,
          POP.Estado,
          IF(POP.Estado='Activo', (POP.Cantidad - IFNULL(PR.Remisionada, 0)), 0) as Pendiente
     
         FROM Producto_Orden_Pedido POP
         Left Join 
            (       Select Sum(PR.Cantidad) as Remisionada,
                PR.Id_Producto, R.Id_Orden_Pedido
                FROM Producto_Remision PR 
                Inner Join Remision R on PR.Id_Remision = R.Id_Remision
                Inner Join Orden_Pedido OP On OP.Id_Orden_Pedido = R.Id_Orden_Pedido
                WHERE OP.Id_Orden_Pedido = '$modelo[Id_Orden_Pedido]' and R.Estado !='Anulada'
                Group By OP.Id_Orden_Pedido, PR.Id_Producto
            ) PR on PR.Id_Producto = POP.Id_Producto and PR.Id_Orden_Pedido = POP.Id_Orden_Pedido
            $condicion 
         
         HAVING Pendiente >0 ";
        //  echo $query; exit;
    return $query;
}

function GetCum($id_producto)
{
    global $queryObj;

    $query = "SELECT Codigo_Cum FROM Producto WHERE Id_Producto=$id_producto";
    $queryObj->SetQuery($query);
    $cum = $queryObj->ExecuteQuery('simple');

    return $cum['Codigo_Cum'];
}

function GetQueryProducto($id_producto)
{
    global $modelo, $grupo, $mes;
    $mes = (int)$mes;
    if ($mes > 0) {
        $vencimiento = "AND Date(I.Fecha_Vencimiento) >= DATE_ADD(now(), interval $mes MONTH)";
    }
    
    $condicion_principal = "AND  P.Id_Producto = $id_producto";

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
                PO.Cantidad as Pendiente,
                '0' AS Remisionada,
                PO.Costo,
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
                left JOIN (SELECT Precio, Codigo_Cum,  REPLACE(Codigo_Cum,'-','') as Cum FROM Precio_Regulado group  BY Cum ) PRG ON P.Codigo_Cum = PRG.Codigo_Cum
                LEFT JOIN (
                    SELECT I.Id_Producto, 
                    SUM(I.Cantidad - I.Cantidad_Apartada - I.Cantidad_Seleccionada) AS Disponible, 
                    G.Nombre AS Grupo_Estiba,
                    GROUP_CONCAT( DISTINCT I.Lote SEPARATOR '|') AS Lotes_Disponibles
                    FROM Inventario_Nuevo I 
                    INNER JOIN Estiba E ON E.Id_Estiba = I.Id_Estiba
                    
                    INNER JOIN Grupo_Estiba G ON G.Id_Grupo_Estiba = E.Id_Grupo_Estiba
                    WHERE E.Id_Bodega_Nuevo ='$modelo[rem_origen]'
                    $vencimiento
                    AND (I.Cantidad - I.Cantidad_Apartada - I.Cantidad_Seleccionada)>0
                    
                    GROUP BY I.Id_Producto, G.Id_Grupo_Estiba
                ) I ON I.Id_Producto = P.Id_Producto
                WHERE PO.Id_Orden_Pedido ='$modelo[Id_Orden_Pedido]'
                $condicion_principal
                GROUP BY P.Id_Producto
                Having Pendiente >0
            ";


    return $query;
}


function GetLotes($producto)
{
    global  $queryObj, $condicion_lotes;
    $condicionBodega = ' 
     INNER JOIN Producto PRD
     On I.Id_Producto=PRD.Id_Producto
     INNER JOIN Subcategoria SubC ON PRD.Id_Subcategoria = SubC.Id_Subcategoria ';


    $having = "  HAVING Cantidad>0 ORDER BY I.Fecha_Vencimiento ASC";


    $query1 = "SELECT I.Id_Inventario_Nuevo, I.Id_Producto,I.Lote,(I.Cantidad-(I.Cantidad_Apartada+I.Cantidad_Seleccionada)) as Cantidad,
     I.Fecha_Vencimiento, '$producto[Precio]' as Precio, 0 as Cantidad_Seleccionada FROM Inventario_Nuevo I 
     $condicionBodega $condicion_lotes AND  I.Id_Producto= $producto[Id_Producto] " . $having;


    $queryObj->SetQuery($query1);
    $lotes = $queryObj->ExecuteQuery('Multiple');

    return $lotes;
}

function SetCondicionLotes()
{
    #enviar grupo
    global $modelo, $nuevafecha, $grupo, $mes;
    $condicion_principal = " INNER JOIN Estiba E ON I.Id_Estiba=E.Id_Estiba
     INNER JOIN Bodega_Nuevo B ON E.Id_Bodega_Nuevo = B.Id_Bodega_Nuevo
     INNER JOIN Grupo_Estiba G ON G.Id_Grupo_Estiba = E.Id_Grupo_Estiba
     WHERE B.Id_Bodega_Nuevo = '$modelo[rem_origen]' ";

    if ($mes != '-1') {
        $condicion_principal .= " AND I.Fecha_Vencimiento >= '$nuevafecha' ";
    }

    return $condicion_principal;
}

function GetNombreProducto($id)
{
    global $queryObj;

    $query = "SELECT Nombre_Comercial FROM Producto WHERE Id_Producto =$id";
    $queryObj->SetQuery($query);
    $nom = $queryObj->ExecuteQuery('simple');
    return $nom['Nombre_Comercial'];
}

function RegistrarActividadCambio($id)
{
    global $nombre_antiguo, $nombre_nuevo, $modelo;

    $ActividadDis['Fecha'] = date("Y-m-d H:i:s");
    $ActividadDis["Id_Orden_Pedido"] = $id;
    $ActividadDis["Identificacion_Funcionario"] = $modelo['funcionario'];

    $ActividadDis["Detalle"] = "Se realizo el cambio de producto " . $nombre_antiguo . " por " . $nombre_nuevo;
    $ActividadDis["Estado"] = "Edicion";

    $oItem = new complex("Actividad_Orden_Pedido", "Id_Actividad_Orden_Pedido");
    foreach ($ActividadDis as $index => $value) {
        $oItem->$index = $value;
    }
    $oItem->save();
    unset($oItem);
    
    $oItem = new complex("Cambio_Producto_Orden_Pedido", "Id_Cambio_Producto_Orden_Pedido");
    $oItem->Id_Orden_Pedido	= $id;
    $oItem->Id_Producto_Antiguo	= $modelo['Id_Producto_Viejo'];
    $oItem->Id_Producto_Nuevo = $modelo['Id_Producto_Nuevo'];
    $oItem->Identificacion_Funcionario = $modelo['funcionario'];
    $oItem->Fecha = date("Y-m-d H:i:s");
    $oItem->save();

    $id_registro = $oItem->getId();
    unset($oItem);
    return $id_registro;
}
