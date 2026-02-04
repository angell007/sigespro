<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

date_default_timezone_set('America/Bogota');

//require_once('../../config/start.inc.php');
include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.http_response.php');
include_once('../../class/class.utility.php');

$http_response = new HttpResponse();
$queryObj = new QueryBaseDatos();
$util = new Utility();

$tipo = (isset($_REQUEST['tiporemision']) ? $_REQUEST['tiporemision'] : '');
$cliente = (isset($_REQUEST['id_destino']) ? $_REQUEST['id_destino'] : '');
$mes = isset($_REQUEST['mes']) ? $_REQUEST['mes'] : '';
$tipo_bodega = '';

$tipo_bodega = isset($_REQUEST['modelo']) ? $_REQUEST['modelo'] : '';
$id_origen = isset($_REQUEST['id_origen']) ? $_REQUEST['id_origen'] : '';
$id_grupo = isset($_REQUEST['id_grupo']) ? $_REQUEST['id_grupo'] : '';
$grupo= isset($_REQUEST['grupo']) ? $_REQUEST['grupo'] : '';

$grupo = (array) json_decode($grupo,true);
$id_categoria_nueva = isset($_REQUEST['id_categoria_nueva']) ? $_REQUEST['id_categoria_nueva'] : '';

$tipo_bodega = explode('-', $tipo_bodega); // origen y desitino

if ($mes > '0') {
    $hoy = date("Y-m-t", strtotime(date('Y-m-d')));
    $nuevafecha = strtotime('+' . $mes . ' months', strtotime($hoy));
    $nuevafecha = date('Y-m-d', $nuevafecha);
} else {
    $nuevafecha = date('Y-m-d');
}
$condicion_principal = '';
$condicion = SetCondiciones($_REQUEST);
$query = GetQuery($tipo);
//echo $query;
$queryObj->SetQuery($query);
$productos = $queryObj->ExecuteQuery('Multiple');


$productos = GetLotes($productos);

echo json_encode($productos);

function SetCondiciones($req)
{   
    
    global $nuevafecha, $condicion_principal, $tipo_bodega, $id_origen, $id_grupo, $mes,$grupo;

    $condicion = '';

    if ($tipo_bodega[0] == 'Bodega') {
        /*if (count($grupo)>0) {
            $condicion_grupo = $grupo['Id_Grupo'] != -1 && $grupo['Id_Grupo'] != 0 ? " AND  G.Id_Grupo_Estiba = $grupo[Id_Grupo]" : '';
        } else {
            $condicion_grupo = $id_grupo != -1 && $grupo['Id_Grupo'] != 0 ? " AND  G.Id_Grupo_Estiba = $id_grupo" : '';
        }*/

        $condicion_principal .= '
            INNER JOIN Estiba E ON I.Id_Estiba=E.Id_Estiba
            INNER JOIN Bodega_Nuevo B ON E.Id_Bodega_Nuevo = B.Id_Bodega_Nuevo
            INNER JOIN Grupo_Estiba G ON G.Id_Grupo_Estiba = E.Id_Grupo_Estiba
            WHERE E.Estado = "Disponible" AND  B.Id_Bodega_Nuevo = ' . $id_origen /*. $condicion_grupo*/;

        /*if ($tipo_bodega[1] != 'Bodega' && ($grupo['Fecha_Vencimiento'] =='Si' && $mes != '-1')) {
           
            $condicion_principal .= "  AND I.Fecha_Vencimiento>='$nuevafecha' ";
        }*/

    } else if ($tipo_bodega[0] == 'Punto') {
        $condicion_principal = "
        INNER JOIN Estiba E ON I.Id_Estiba=E.Id_Estiba
        INNER JOIN Punto_Dispensacion B ON E.Id_Punto_Dispensacion = B.Id_Punto_Dispensacion
        INNER JOIN Grupo_Estiba G ON G.Id_Grupo_Estiba = E.Id_Grupo_Estiba
        WHERE E.Estado = 'Disponible' AND B.Id_Punto_Dispensacion = $id_origen"; /*AND 
         $condicion_grupo*/
    }


    if (isset($req['nombre']) && $req['nombre']) {
        $condicion .= " AND (PRD.Nombre_Comercial LIKE '%$req[nombre]%' OR  CONCAT(PRD.Principio_Activo,' ',PRD.Presentacion,' ',PRD.Concentracion,' ', PRD.Cantidad,' ', PRD.Unidad_Medida) LIKE '%$req[nombre]%' )";
    }

    if (isset($req['cum'])) {
        $cum = trim($req['cum']);
        if ($cum !== '') {
            $condicion .= " AND PRD.Codigo_Cum LIKE '%" . $cum . "%'";
        }
    }

    if (isset($req['lab_com']) && $req['lab_com']) {
        $condicion .= " AND PRD.Laboratorio_Comercial LIKE '%" . $req['lab_com'] . "%'";
    }

    if (isset($req['cod_barra']) && $req['cod_barra'] != '') {
        $condicion .= " AND PRD.Codigo_Barras LIKE '%" . $req['cod_barra'] . "%'";
    }
    return $condicion;
}

function GetQuery($tipo)
{
    global $condicion, $condicion_principal, $cliente, $queryObj, $tipo_bodega;
    $having = " GROUP BY I.Id_Producto HAVING Cantidad_Disponible>0 ORDER BY Nombre_Comercial";
    $id_origen = $_REQUEST['id_origen'];
    // $subquery_bodega = $tipo_bodega[0] == 'Bodega' ? "(SELECT Aplica_Separacion_Categorias FROM Bodega WHERE Id_Bodega = $id_origen) AS Aplica_Separacion_Categorias," : "";
    $subquery_bodega =  "";
    $inventarioContratoJoin = 'LEFT JOIN (
            SELECT Id_Inventario_Nuevo,
                   SUM(Cantidad) AS Cantidad,
                   SUM(Cantidad_Apartada) AS Cantidad_Apartada,
                   SUM(Cantidad_Seleccionada) AS Cantidad_Seleccionada
            FROM Inventario_Contrato
            GROUP BY Id_Inventario_Nuevo
        ) IC ON I.Id_Inventario_Nuevo = IC.Id_Inventario_Nuevo';



    if ($tipo == 'Interna') {
        $query = 'SELECT SubC.Nombre AS Subcategoria, SubC.Separable AS Categoria_Separable, PRD.Id_Subcategoria,  PRD.Id_Producto,
         IFNULL((SELECT Costo_Promedio  FROM Costo_Promedio WHERE Id_Producto = PRD.Id_Producto),"0") as  Precio, PRD.Embalaje,
         SUM(I.Cantidad - (I.Cantidad_Apartada+I.Cantidad_Seleccionada) - IFNULL( (IC.Cantidad),"0")) as Cantidad_Disponible,
         CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," ", PRD.Cantidad," ", PRD.Unidad_Medida) as Nombre, 
         PRD.Nombre_Comercial, PRD.Laboratorio_Comercial,PRD.Laboratorio_Generico,PRD.Codigo_Cum, PRD.Cantidad_Presentacion, 0 as Seleccionado, NULL as Cantidad, ' . $subquery_bodega . ' (
                CASE
                WHEN PRD.Gravado="Si" THEN (SELECT Valor FROM Impuesto WHERE Valor>0 ORDER BY Id_Impuesto DESC LIMIT 1)
                WHEN PRD.Gravado="No"  THEN 0
              END
            ) as Impuesto, IFNULL((SELECT Costo_Promedio  FROM Costo_Promedio WHERE Id_Producto = PRD.Id_Producto),"0") as Costo 
            FROM Inventario_Nuevo I
            '.$inventarioContratoJoin.'
            INNER JOIN Producto PRD
            On I.Id_Producto=PRD.Id_Producto 
            INNER JOIN Subcategoria SubC ON PRD.Id_Subcategoria = SubC.Id_Subcategoria ' . $condicion_principal . $condicion . $having;

    }else if ($tipo == 'Contrato') {
        $condicionContrato = " AND IC.Id_Contrato = '$cliente'";
    
            $query = 'SELECT SubC.Nombre AS Subcategoria, SubC.Separable AS Categoria_Separable, PRD.Id_Subcategoria,  PRD.Id_Producto,
                        IFNULL( PC.Precio,"0") as  Precio, PRD.Embalaje,
                        SUM(IC.Cantidad - (IC.Cantidad_Apartada+IC.Cantidad_Seleccionada)) as Cantidad_Disponible,
                        CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," ", PRD.Cantidad," ", PRD.Unidad_Medida) as Nombre, 
                        PRD.Nombre_Comercial, PRD.Laboratorio_Comercial,PRD.Laboratorio_Generico,PRD.Codigo_Cum, PRD.Cantidad_Presentacion, 0 as Seleccionado, 
                        NULL as Cantidad, ' . $subquery_bodega . ' 
                            (
                                CASE
                                WHEN PRD.Gravado="Si" THEN (SELECT Valor FROM Impuesto WHERE Valor>0 ORDER BY Id_Impuesto DESC LIMIT 1)
                                WHEN PRD.Gravado="No"  THEN 0
                                END
                            ) as Impuesto, IFNULL((SELECT Costo_Promedio  FROM Costo_Promedio WHERE Id_Producto = PRD.Id_Producto),"0") as Costo 
                            FROM Inventario_Contrato IC
                            LEFT JOIN Inventario_Nuevo I ON I.Id_Inventario_Nuevo = IC.Id_Inventario_Nuevo
                            LEFT JOIN Producto_Contrato PC ON IC.Id_Producto_Contrato = PC.Id_Producto_Contrato
                            INNER JOIN Producto PRD
                            On I.Id_Producto=PRD.Id_Producto 
                            INNER JOIN Subcategoria SubC ON PRD.Id_Subcategoria = SubC.Id_Subcategoria '  . $condicion_principal . $condicionContrato  . $condicion . $having;
            //   echo $query;          
    }else {
        $query1 = "SELECT * FROM Cliente WHERE Id_Cliente=" . $cliente;
        $queryObj->SetQuery($query1);
        $datoscliente = $queryObj->ExecuteQuery('simple');

        $query = ' SELECT T.*, (
                    CASE WHEN PRG.Codigo_Cum IS NOT NULL THEN "Si" WHEN PRG.Codigo_Cum IS  NULL THEN "No" END ) as Regulado,
    
                    ( CASE WHEN PRG.Codigo_Cum IS NOT NULL THEN PRG.Precio WHEN PRG.Codigo_Cum IS  NULL THEN 0 END ) as Precio_Regulado 
                FROM
                    (SELECT 
                        SubC.Nombre as Subcategoria, 
                        SubC.Separable AS Categoria_Separable,
                        PRD.Id_Subcategoria,  PRD.Id_Producto,
                        IFNULL((IF (IFNULL((SELECT Precio FROM Precio_Regulado WHERE Codigo_Cum=PRD.Codigo_Cum ORDER BY Precio desc LIMIT 1),0) <  LG.Precio 
                        AND  IFNULL((SELECT Precio FROM Precio_Regulado WHERE Codigo_Cum=PRD.Codigo_Cum ORDER BY Precio desc LIMIT 1),0)>0 , IFNULL((SELECT ROUND( Precio,2) FROM Precio_Regulado WHERE Codigo_Cum=PRD.Codigo_Cum ORDER BY Precio desc LIMIT 1),0),LG.Precio   )),0) as Precio,
                    0 as Seleccionado, NULL as Cantidad,  PRD.Embalaje,         
                    SUM(I.Cantidad-(I.Cantidad_Apartada+I.Cantidad_Seleccionada) - IFNULL( (IC.Cantidad),"0")) as Cantidad_Disponible,
                    CONCAT_WS(" ", PRD.Principio_Activo, PRD.Presentacion, PRD.Concentracion, PRD.Cantidad, PRD.Unidad_Medida) as Nombre,
                    PRD.Nombre_Comercial, 
                    PRD.Laboratorio_Comercial,
                    PRD.Laboratorio_Generico,
                    PRD.Codigo_Cum,
                    PRD.Cantidad_Presentacion,
                    (
                        CASE
                        WHEN PRD.Gravado="Si" THEN (SELECT Valor FROM Impuesto WHERE Valor>0 ORDER BY Id_Impuesto DESC LIMIT 1)
                        WHEN PRD.Gravado="No"  THEN 0
                        END
                    ) as Impuesto,    
                    IFNULL((SELECT Costo_Promedio  FROM Costo_Promedio WHERE Id_Producto = PRD.Id_Producto),"0") as Costo,  
                    REPLACE(PRD.Codigo_Cum,"-","") as Cum 
                    FROM Inventario_Nuevo I
                    '.$inventarioContratoJoin.'
                    INNER JOIN Producto PRD On I.Id_Producto=PRD.Id_Producto
                    INNER JOIN Subcategoria SubC ON PRD.Id_Subcategoria = SubC.Id_Subcategoria   
                    INNER JOIN Producto_Lista_Ganancia LG ON PRD.Codigo_Cum = LG.Cum ' . $condicion_principal . $condicion . ' AND LG.Id_Lista_Ganancia =' . $datoscliente['Id_Lista_Ganancia'] . $having . '  ) 
                    T left JOIN (SELECT Precio, Codigo_Cum,  REPLACE(Codigo_Cum,"-","") as Cum FROM Precio_Regulado group  BY Cum ) PRG ON T.Cum=PRG.Cum ';
        }
// echo $query;
// exit;
    return $query;
}

function GetLotes($productos)
{
    global  $queryObj, $condicion_principal, $tipo, $tipo_bodega;

    $inventarioContratoJoin = 'LEFT JOIN (
            SELECT Id_Inventario_Nuevo,
                   SUM(Cantidad) AS Cantidad
            FROM Inventario_Contrato
            GROUP BY Id_Inventario_Nuevo
        ) IC ON I.Id_Inventario_Nuevo = IC.Id_Inventario_Nuevo';

    $condicionBodega = ' ';
    if ($tipo_bodega[0] == 'Bodega') {
        $condicionBodega .= ' 
            INNER JOIN Producto PRD
            On I.Id_Producto=PRD.Id_Producto
            INNER JOIN Subcategoria SubC ON PRD.Id_Subcategoria = SubC.Id_Subcategoria ';
    }
  

    $resultado = [];
    $having = "  HAVING Cantidad>0 ORDER BY I.Fecha_Vencimiento ASC";
    $i = -1;
    $pos = 0;
    foreach ($productos as  $value) {
        $i++;
        if ($tipo == 'Cliente') {
            $productos[$i]['Costo'] = GetCosto($value['Id_Producto'], $value['Costo']);
        }
        if ($tipo == 'Contrato') {
            $query1 = "SELECT I.Id_Inventario_Nuevo,I.Id_Producto, (IC.Cantidad-(IC.Cantidad_Apartada+IC.Cantidad_Seleccionada)) as Cantidad,
                              I.Fecha_Vencimiento,$value[Precio] as Precio,0 as Cantidad_Seleccionada FROM Inventario_Contrato IC
                       INNER JOIN Inventario_Nuevo I ON I.Id_Inventario_Nuevo = IC.Id_Inventario_Nuevo";

        }else{
            $query1 = "SELECT I.Id_Inventario_Nuevo, 
                              I.Id_Producto,I.Lote,(I.Cantidad-(I.Cantidad_Apartada+I.Cantidad_Seleccionada)-IFNULL(IC.Cantidad,0)) as Cantidad,
                              I.Fecha_Vencimiento,$value[Precio] as Precio, 0 as Cantidad_Seleccionada FROM Inventario_Nuevo I ";

        }

        if ($tipo != 'Contrato') {
            $query1 .= $inventarioContratoJoin;
        }
        $query1 .= $condicionBodega . $condicion_principal . " AND I.Id_Producto= $value[Id_Producto] " . $having;

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

function GetCosto($idProducto)
{
    $oCon = new consulta();
    $query = 'SELECT Costo_Promedio FROM Costo_Promedio WHERE Id_Producto = '.$idProducto;
    $oCon->setQuery($query);
    $costo = $oCon->getData();
    unset($oCon);
    return $costo['Costo_Promedio'];
}
 
