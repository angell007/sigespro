<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );


$query = 'SELECT CN.*,
            PR.Nombre as NombreProveedor
            
           FROM Orden_Compra_Nacional CN 
           INNER JOIN Proveedor PR 
           ON PR.Id_Proveedor = CN.Id_Proveedor
           WHERE CN.Id_Orden_Compra_Nacional='.$id ;

$oCon= new consulta();
$oCon->setQuery($query);
$encabezado = $oCon->getData();
unset($oCon);


$query2 = 'SELECT 
                IFNULL(CONCAT( P.Principio_Activo, " ", P.Presentacion, " ", P.Concentracion, " (", P.Nombre_Comercial,") ", P.Cantidad," ", P.Unidad_Medida, " " ),CONCAT(P.Nombre_Comercial," LAB-", P.Laboratorio_Comercial)) as producto, 
                POCN.Costo as Costo , 
                POCN.Costo_Promedio as Costo_Promedio , 
                POCN.Cantidad as Cantidad, 
                POCN.Iva as Iva, 
                POCN.Total as Total,
                (POCN.Total * (POCN.Iva/100)) AS Iva_Acu,
                POCN.Id_Producto as Id_Producto,
                IFNULL(PRG.Precio_Venta, -1) as Precio_Regulado,
                P.Cantidad_Presentacion AS Presentacion,
                "0" as Rotativo, 
                Concat_Ws("\n", P.Embalaje,"(Inventario:", IFnull( I.Disponible, 0), ")") as Embalaje
           FROM Producto_Orden_Compra_Nacional POCN 
           INNER JOIN Producto P ON P.Id_Producto = POCN.Id_Producto 
           LEFT JOIN(
               SELECT 
               SUM(I.Cantidad - I.Cantidad_Seleccionada - I.Cantidad_Apartada) as Disponible, 
               I.Id_Producto
               From Inventario_Nuevo I 
               Inner Join Estiba E on E.Id_Estiba = I.Id_Estiba 
               Where E.Id_Bodega_Nuevo = 1
               group by I.Id_Producto
               ) I on I.Id_Producto = P.Id_Producto
           LEFT JOIN Precio_Regulado PRG on PRG.Codigo_Cum = P.Codigo_Cum
           WHERE POCN.Id_Orden_Compra_Nacional ='.$id .'
           ORDER BY producto ASC';
           
           
$oCon= new consulta();
$oCon->setQuery($query2);
$oCon->setTipo('Multiple');
$productos = $oCon->getData();
unset($oCon);


$resultado["Datos"]=$encabezado;
$resultado["Productos"]=$productos;

echo json_encode($resultado);
          
?>