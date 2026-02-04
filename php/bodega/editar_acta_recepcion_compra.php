<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;

$query ="SELECT AR.Tipo, IFNULL(AR.Id_Orden_Compra_Nacional,AR.Id_Orden_Compra_Internacional) AS Id_Orden_Compra, ( CASE WHEN AR.Tipo = 'Nacional' THEN (SELECT Codigo FROM Orden_Compra_Nacional OCN WHERE OCN.Id_Orden_Compra_Nacional = AR.Id_Orden_Compra_Nacional) WHEN AR.Tipo = 'Internacional' THEN (SELECT Codigo FROM Orden_Compra_Internacional OCI WHERE OCI.Id_Orden_Compra_Internacional = AR.Id_Orden_Compra_Internacional) END ) AS Codigo, AR.Identificacion_Funcionario, AR.Id_Bodega, AR.Id_Proveedor, (SELECT P.Nombre FROM Proveedor P WHERE P.Id_Proveedor=AR.Id_Proveedor) AS Proveedor, AR.Observaciones FROM Acta_Recepcion AR WHERE AR.Id_Acta_Recepcion =$id";

$con = new consulta();
$con->setQuery($query);
$res = $con->getData();
unset($con);

$query = "SELECT Id_Factura_Acta_Recepcion, Factura, Fecha_Factura, '' AS Archivo_Factura, true AS Required FROM Factura_Acta_Recepcion WHERE Id_Acta_Recepcion=$id";

$con = new consulta();
$con->setQuery($query);
$con->setTipo('Multiple');
$res2 = $con->getData();
unset($con);

switch($res["Tipo"]){
    
    case "Nacional":{
        $query = 'SELECT  
                IFNULL(CONCAT( P.Principio_Activo, " ", P.Presentacion, " ", P.Concentracion, " (", P.Nombre_Comercial,") ", P.Cantidad," ", P.Unidad_Medida, " LAB- ", P.Laboratorio_Comercial ),CONCAT(P.Nombre_Comercial, " LAB-",P.Laboratorio_Comercial)) as NombreProducto,
                SUM(POCN.Cantidad) as CantidadProducto,
                POCN.Costo as CostoProducto,
                POCN.Id_Producto_Orden_Compra_Nacional AS Id_Producto_Orden_Compra,
                OCN.Codigo,
                P.Embalaje,
                P.Id_Producto as Id_Producto,
                P.Codigo_Cum as Codigo_CUM,
                IF(P.Gravado="Si",19,0) AS Impuesto,
                P.Imagen AS Foto,
                P.Id_Categoria,
                P.Peso_Presentacion_Regular AS Peso,
                IF(P.Codigo_Barras IS NULL, "No", "Si") AS Codigo_Barras,
                0 as Cantidad,
                0 as Cantidad_Band,	
                0 as Precio,
                0 as Subtotal,
                0 as Iva,	
                "" as Lote,
                "" as Fecha_Vencimiento,	
                0 as No_Conforme,
                false as Checkeado,
                true AS Disabled,
                true AS Required
           FROM Producto_Orden_Compra_Nacional POCN 
               INNER JOIN Producto P 
                ON P.Id_Producto = POCN.Id_Producto 
               INNER JOIN Orden_Compra_Nacional OCN
                ON OCN.Id_Orden_Compra_Nacional = POCN.Id_Orden_Compra_Nacional
           WHERE OCN.Codigo ="'.$res["Codigo"].'" GROUP BY POCN.Id_Producto' ;
           
        break;
    }
    case "Internacional":{
        $query = 'SELECT 
                 CONCAT( P.Principio_Activo, " ", P.Presentacion, " ", P.Concentracion, " (", P.Nombre_Comercial,") ", P.Cantidad," ", P.Unidad_Medida, " " ) as NombreProducto,
                POCN.Cantidad as CantidadProducto,
                POCN.Costo as CostoProducto,
                POCN.Id_Orden_Compra_Internacional AS Id_Producto_Orden_Compra,
                OCN.Codigo,
                P.Id_Producto as Id_Producto,
                P.Codigo_Cum as Codigo_CUM,
                IF(P.Gravado="Si",19,0) AS Impuesto,
                P.Imagen AS Foto,
                P.Id_Categoria,
                P.Peso_Presentacion_Regular AS Peso,
                IF(P.Codigo_Barras IS NULL, "No", "Si") AS Codigo_Barras,
                0 as Cantidad,	
                0 as Cantidad_Band,	
                0 as Precio,
                0 as Subtotal,
                0 as Iva,	
                "" as Lote,
                "" as Fecha_Vencimiento,	
                0 as No_Conforme,
                false as Checkeado,
                true AS Disabled,
                true AS Required
           FROM Producto_Orden_Compra_Internacional POCN 
               INNER JOIN Producto P 
                ON P.Id_Producto = POCN.Id_Producto 
               INNER JOIN Orden_Compra_Internacional OCN
                ON OCN.Id_Orden_Compra_Internacional = POCN.Id_Orden_Compra_Internacional
           WHERE OCN.Codigo ="'.$res["Codigo"].'" GROUP BY POCN.Id_Producto' ;
        
        break;
    }
}

$con = new consulta();
$con->setQuery($query);
$con->setTipo('Multiple');
$productos = $con->getData();
unset($con);

foreach ($productos as $i => $prod) {
    $query = "SELECT PAR.Id_Producto_Acta_Recepcion, PAR.Id_Producto, PAR.Cantidad, $prod[CantidadProducto] AS CantidadProducto, PAR.Precio, PAR.Fecha_Vencimiento, PAR.Lote, PAR.Impuesto, PAR.Subtotal, PNC.Id_Causal_No_Conforme AS No_Conforme, PNC.Cantidad AS Cantidad_No_Conforme, (PAR.Subtotal*(PAR.Impuesto/100)) AS Iva FROM Producto_Acta_Recepcion PAR LEFT JOIN Producto_No_Conforme PNC ON PAR.Id_Acta_Recepcion=PNC.Id_Acta_Recepcion AND PAR.Id_Producto=PNC.Id_Producto WHERE PAR.Id_Acta_Recepcion=$id AND PAR.Id_Producto=$prod[Id_Producto] GROUP BY PAR.Id_Producto, PAR.Lote";

    $con = new consulta();
    $con->setQuery($query);
    $con->setTipo('Multiple');
    $products = $con->getData();
    unset($con);

    $array = ["Id_Producto_Acta_Recepcion" => 0,"Id_Producto" => $products[0]["Id_Producto"], "Cantidad" => 0, "Precio" => 0, "Fecha_Vencimiento" => '', "Lote" => '', "Impuesto" => 0, "Subtotal" => 0, "No_Conforme" => '', "Cantidad_No_Conforme" => '', "CantidadProducto" => $prod['CantidadProducto'], "Iva" => 0];

    array_push($products, $array);

    $productos[$i]['producto'] = $products;
} 

$resultado['encabezado'] = $res;
$resultado['facturas'] = $res2;
$resultado['producto'] = $productos;

echo json_encode($resultado);
          
?>