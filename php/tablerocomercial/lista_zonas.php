<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = "SELECT
      ZC.Nombre,ZC.Id_Zona,
      Rep.Ventas-Rep.Notas AS Ventas,
      (Rep.Ventas-Rep.Notas)*100 /(TV.TotalVentas-TV.Notas) AS Porcentaje
      FROM Factura_Venta FV 
      INNER JOIN Cliente C ON FV.Id_Cliente = C.Id_Cliente
      INNER JOIN Zona ZC ON C.Id_Zona = ZC.Id_Zona

      INNER JOIN (
            SELECT 
            SUM(PFV.Subtotal) AS Ventas, 
            C.Id_Zona, 
            Ifnull(SUM(NC.Nota),0)+ ifnull(SUM(NG.Nota),0)AS Notas,
            DATE_FORMAT(FV.Fecha_Documento, '%Y-%m') AS Fecha_Mes
            FROM Producto_Factura_Venta PFV
            INNER JOIN Factura_Venta FV ON PFV.Id_Factura_Venta = FV.Id_Factura_Venta
            INNER JOIN Cliente C ON FV.Id_Cliente = C.Id_Cliente
            
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
            
            GROUP BY C.Id_Zona, Fecha_Mes
      ) Rep ON Rep.Id_Zona=ZC.Id_Zona and Rep.Fecha_Mes=DATE_FORMAT(FV.Fecha_Documento, '%Y-%m')

      INNER JOIN (SELECT SUM(PFV.Subtotal) AS TotalVentas,
                  Ifnull(SUM(NC.Nota),0)+ ifnull(SUM(NG.Nota),0)AS Notas,
                  DATE_FORMAT(FV.Fecha_Documento, '%Y-%m') AS Fecha_Mes
                  FROM Producto_Factura_Venta PFV
                  INNER JOIN Factura_Venta FV ON PFV.Id_Factura_Venta = FV.Id_Factura_Venta

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
                  GROUP BY Fecha_Mes
            )TV ON TV.Fecha_Mes=DATE_FORMAT(FV.Fecha_Documento, '%Y-%m')


      WHERE DATE_FORMAT(FV.Fecha_Documento, '%Y-%m')= DATE_FORMAT(NOW(), '%Y-%m')
      AND FV.Estado != 'Anulada' 
      GROUP BY C.Id_Zona Order BY Porcentaje DESC";


$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);

echo json_encode($resultado);

