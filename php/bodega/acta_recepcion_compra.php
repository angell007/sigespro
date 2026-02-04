<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$codigo = ( isset( $_REQUEST['codigo'] ) ? $_REQUEST['codigo'] : '' );
$tipoCompra = ( isset( $_REQUEST['compra'] ) ? $_REQUEST['compra'] : '' );

switch($tipoCompra){
    
    case "Nacional":{
        $query = 'SELECT  
                IFNULL(CONCAT( P.Principio_Activo, " ", P.Presentacion, " ", P.Concentracion, " (", P.Nombre_Comercial,") ", P.Cantidad," ", P.Unidad_Medida, " LAB- ", P.Laboratorio_Comercial ),CONCAT(P.Nombre_Comercial, " LAB-",P.Laboratorio_Comercial)) as NombreProducto,
                SUM(POCN.Cantidad) as CantidadProducto,
                POCN.Costo as CostoProducto,
                POCN.Id_Producto_Orden_Compra_Nacional AS Id_Producto_Orden_Compra,
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
           WHERE OCN.Codigo ="'.$codigo.'" GROUP BY POCN.Id_Producto' ;
           
           $query1 ="SELECT 'Nacional' AS Tipo, Id_Orden_Compra_Nacional AS Id_Orden_Compra, Codigo, Identificacion_Funcionario, Id_Bodega, (SELECT P.Nombre FROM Proveedor P WHERE P.Id_Proveedor=OCN.Id_Proveedor) AS Proveedor, OCN.Id_Proveedor FROM Orden_Compra_Nacional OCN WHERE Codigo = '".$codigo."'";
           
        break;
    }
    case "Internacional":{
        $query = 'SELECT 
                 CONCAT( P.Principio_Activo, " ", P.Presentacion, " ", P.Concentracion, " (", P.Nombre_Comercial,") ", P.Cantidad," ", P.Unidad_Medida, " " ) as NombreProducto,
                POCN.Cantidad as CantidadProducto,
                POCN.Costo as CostoProducto,
                POCN.Id_Orden_Compra_Internacional AS Id_Producto_Orden_Compra,
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
           WHERE OCN.Codigo ="'.$codigo.'" GROUP BY POCN.Id_Producto' ;
        
        $query1 ="SELECT 'Internacional' AS Tipo, Id_Orden_Compra_Internacional AS Id_Orden_Compra, Codigo, Identificacion_Funcionario, Id_Bodega, (SELECT P.Nombre FROM Proveedor P WHERE P.Id_Proveedor=OCI.Id_Proveedor) AS Proveedor, OCI.Id_Proveedor FROM  Orden_Compra_Internacional OCI WHERE Codigo = '".$codigo."'";
        
        break;
    }
}

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$res = $oCon->getData();
unset($oCon);


$oCon= new consulta();
$oCon->setQuery($query1);
$res1 = $oCon->getData();
unset($oCon);

$resultado['encabezado'] = $res1;
$resultado['producto'] = $res;

$i = -1;
foreach ($res as $value) {$i++;
    $resultado['producto'][$i]['producto'][] = $res[$i];
}

echo json_encode($resultado);
          
?>