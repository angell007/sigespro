<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once '../../config/start.inc.php';
include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';

$query = "SELECT
        C.Id_Cliente,
        C.Nombre,
        (IFNULL(SUM(PFV.Subtotal), 0))-(Ifnull(SUM(NC.Nota),0)+ ifnull(SUM(NG.Nota),0)) AS Ventas
        FROM Producto_Factura_Venta PFV
        INNER JOIN Factura_Venta FV ON PFV.Id_Factura_Venta = FV.Id_Factura_Venta
        INNER JOIN Cliente C ON C.Id_Cliente = FV.Id_Cliente

        LEFT JOIN (
                SELECT SUM(PNC.Subtotal) AS Nota, NC.Id_Factura, PNC.Id_Producto,
                PNC.Lote
                FROM Producto_Nota_Credito PNC
                INNER JOIN Nota_Credito NC ON NC.Id_Nota_Credito = PNC.Id_Nota_Credito 
                GROUP BY NC.Id_Factura, PNC.Id_Producto, PNC.Lote
        )NC ON NC.Id_Factura = FV.Id_Factura_Venta and PFV.Id_Producto = NC.Id_Producto AND PFV.Lote= NC.Lote
        
        LEFT JOIN (
                SELECT SUM(PNG.Precio_Nota_Credito*PNG.Cantidad) AS Nota, NG.Id_Factura, PNG.Id_Producto as Id_Producto_Factura_Venta
                FROM Producto_Nota_Credito_Global PNG
                INNER JOIN Nota_Credito_Global NG ON NG.Id_Nota_Credito_Global = PNG.Id_Nota_Credito_Global
                WHERE NG.Tipo_Factura='Factura_Venta'
                GROUP BY NG.Id_Factura, Id_Producto_Factura_Venta
        )NG ON NG.Id_Factura = FV.Id_Factura_Venta and NG.Id_Producto_Factura_Venta = PFV.Id_Producto_Factura_Venta


        WHERE MONTH(FV.Fecha_Documento) = MONTH(NOW()) AND YEAR(FV.Fecha_Documento)= YEAR(NOW())
        GROUP BY FV.Id_Cliente
        ORDER BY Ventas DESC
        LIMIT 10;";

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$clientes = $oCon->getData();
unset($oCon);

foreach ($clientes as $i => $cliente) {

    $query = "SELECT 
                P.Imagen, 
                P.Nombre_Comercial, 
                P.Embalaje,
                SUM(PFV.Cantidad) AS Cantidad, 
                -- SUM(PFV.Subtotal+(PFV.Subtotal * (PFV.Impuesto/100))) as Total
                (IFNULL(SUM(PFV.Subtotal), 0))-(Ifnull(SUM(NC.Nota),0)+ ifnull(SUM(NG.Nota),0)) AS Total
                FROM Producto_Factura_Venta PFV 
                INNER JOIN Producto P ON PFV.Id_Producto = P.Id_Producto 
                INNER JOIN Factura_Venta FV ON PFV.Id_Factura_Venta = FV.Id_Factura_Venta 
                INNER JOIN Cliente C ON C.Id_Cliente = FV.Id_Cliente 
                LEFT JOIN (
                    SELECT SUM(PNC.Subtotal) AS Nota, NC.Id_Factura, PNC.Id_Producto,
                    PNC.Lote
                    FROM Producto_Nota_Credito PNC
                    INNER JOIN Nota_Credito NC ON NC.Id_Nota_Credito = PNC.Id_Nota_Credito 
                    GROUP BY NC.Id_Factura, PNC.Id_Producto, PNC.Lote
                )NC ON NC.Id_Factura = FV.Id_Factura_Venta and PFV.Id_Producto = NC.Id_Producto AND PFV.Lote= NC.Lote
                
                LEFT JOIN (
                    SELECT SUM(PNG.Precio_Nota_Credito*PNG.Cantidad) AS Nota, NG.Id_Factura, PNG.Id_Producto as Id_Producto_Factura_Venta
                    FROM Producto_Nota_Credito_Global PNG
                    INNER JOIN Nota_Credito_Global NG ON NG.Id_Nota_Credito_Global = PNG.Id_Nota_Credito_Global
                    WHERE NG.Tipo_Factura='Factura_Venta'
                    GROUP BY NG.Id_Factura, Id_Producto_Factura_Venta
                )NG ON NG.Id_Factura = FV.Id_Factura_Venta and NG.Id_Producto_Factura_Venta = PFV.Id_Producto_Factura_Venta

                WHERE MONTH(FV.Fecha_Documento) = MONTH(NOW()) 
                AND FV.Id_Cliente = $cliente[Id_Cliente]
                AND YEAR(FV.Fecha_Documento)=YEAR(NOW()) 
                GROUP BY PFV.Id_Producto ORDER BY Total DESC LIMIT 5";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $ventas = $oCon->getData();
    unset($oCon);

    $clientes[$i]['Ventas_Top'] = $ventas;
}

echo json_encode($clientes);
