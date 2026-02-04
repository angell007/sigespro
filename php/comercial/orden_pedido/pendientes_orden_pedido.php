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


if(!$id){
    echo json_encode(['debe ingresar el id']);exit;
}


$query = "SELECT O.*,
                CONCAT(F.Nombres, ' ',F.Apellidos) as Nombre_Funcionario,
                C.Nombre AS Nombre_Cliente, 
                Concat(O.Prefijo, O.Id_Orden_Pedido) as Codigo
                FROM Orden_Pedido O
                INNER JOIN Funcionario F ON F.Identificacion_Funcionario = O.Identificacion_Funcionario
                INNER JOIN Cliente C ON C.Id_Cliente = O.Id_Cliente
            WHERE concat(O.Prefijo, O.Id_Orden_Pedido) = '$id'";
$oCon= new consulta();
$oCon->setQuery($query);
$cabecera = $oCon->getData();
unset($oCon);


$query = "SELECT P.Nombre_Comercial, P.Codigo_Cum,
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
        P.Nombre_Comercial, P.Laboratorio_Comercial, P.Laboratorio_Generico, P.Id_Producto, P.Embalaje, P.Cantidad_Presentacion, 
        PO.Cantidad,
        PO.Costo,
        PO.Precio_Orden,
        PO.Impuesto,
        PO.Estado,
        PO.Observacion,
        if(PO.Estado ='Activo', (PO.Cantidad - IFNULL(PR.Remisionada, 0)), 0) as Pendiente, 
        IFNULL(PR.Remisionada,0) as Remisionada,
        (  ((PO.Impuesto * (PO.Cantidad * PO.Precio_Orden))  /  100)  + ( PO.Cantidad * PO.Precio_Orden )  ) AS Total
        FROM Producto_Orden_Pedido PO
        INNER JOIN Producto P ON P.Id_Producto = PO.Id_Producto
        INNER JOIN Subcategoria S ON S.Id_Subcategoria = P.Id_Subcategoria
        Left Join 
        (       Select Sum(PR.Cantidad) as Remisionada,
                PR.Id_Producto, R.Id_Orden_Pedido
                FROM Producto_Remision PR 
                Inner Join Remision R on PR.Id_Remision = R.Id_Remision
                Inner Join Orden_Pedido OP On OP.Id_Orden_Pedido = R.Id_Orden_Pedido
                WHERE OP.Id_Orden_Pedido = '$cabecera[Id_Orden_Pedido]'
                Group By OP.Id_Orden_Pedido, PR.Id_Producto
        ) PR on PR.Id_Producto = PO.Id_Producto and PR.Id_Orden_Pedido = PO.Id_Orden_Pedido

        WHERE PO.Id_Orden_Pedido = '$cabecera[Id_Orden_Pedido]'";

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$productosOrden = $oCon->getData();
unset($oCon);



echo json_encode(['cabecera'=>$cabecera,'productosOrden'=>$productosOrden]);

?>