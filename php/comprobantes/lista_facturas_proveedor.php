<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = "(SELECT
0 AS Id_Plan_Cuenta,
FAR.Id_Acta_Recepcion,
FAR.Factura AS Codigo,
FAR.Id_Factura_Acta_Recepcion AS Id_Factura,
(SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio)),0)
FROM Producto_Acta_Recepcion PAR2
WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto=0) as Exenta,
(SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio)),0)
FROM Producto_Acta_Recepcion PAR2
WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto!=0) as Gravada,(SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio*(PAR2.Impuesto/100))),0)
FROM Producto_Acta_Recepcion PAR2
WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto!=0)  as Iva,
((SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio)),0)
FROM Producto_Acta_Recepcion PAR2
WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto=0)+ (SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio)),0)
FROM Producto_Acta_Recepcion PAR2
WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto!=0)) AS Total_Compra,
((SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio)),0)
FROM Producto_Acta_Recepcion PAR2
WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto=0)+ (SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio)),0)
FROM Producto_Acta_Recepcion PAR2
WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto!=0)+ (SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio)*(PAR2.Impuesto/100)),0)
FROM Producto_Acta_Recepcion PAR2
WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto!=0)) as Neto_Factura,

 0 AS ValorIngresado, 0 as ValorMayorPagar, 0 as ValorDescuento,

 IF(FC.Pagado IS NOT NULL, ((SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio)),0)
FROM Producto_Acta_Recepcion PAR2
WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto=0)+ (SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio)),0)
FROM Producto_Acta_Recepcion PAR2
WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto!=0)+ (SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio)*(PAR2.Impuesto/100)),0)
FROM Producto_Acta_Recepcion PAR2
WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto!=0)) - FC.Pagado, ((SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio)),0)
FROM Producto_Acta_Recepcion PAR2
WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto=0)+ (SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio)),0)
FROM Producto_Acta_Recepcion PAR2
WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto!=0)+ (SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio)*(PAR2.Impuesto/100)),0)
FROM Producto_Acta_Recepcion PAR2
WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto!=0))) AS Por_Pagar,
IFNULL(FC.Pagado, 0) AS Pagado,
'Facturas Actas' AS Tipo_Factura
FROM
Factura_Acta_Recepcion FAR
INNER JOIN Acta_Recepcion AR
ON FAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion
INNER JOIN Producto_Acta_Recepcion PAR
ON PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion
LEFT JOIN (SELECT Id_Factura, Factura, SUM(Valor) AS Pagado FROM Factura_Comprobante FC INNER JOIN Comprobante C ON FC.Id_Comprobante = C.Id_Comprobante WHERE C.Tipo = 'Egreso' GROUP BY FC.Id_Factura) FC ON FAR.Id_Factura_Acta_Recepcion = FC.Id_Factura AND FAR.Factura = FC.Factura
WHERE AR.Id_Proveedor = $id AND FAR.Estado = 'Pendiente' AND YEAR(AR.Fecha_Creacion) = 2019
GROUP BY FAR.Id_Acta_Recepcion, FAR.Factura ORDER BY FAR.Fecha_Factura DESC)
UNION ALL (
   SELECT CNC.Id_Plan_Cuenta, 'NULL' AS Id_Acta_Recepcion, NC.Documento, NC.Id_Nota_Contable, 0 AS Exenta, 0 AS Gravada, 0 AS Iva, SUM(Credito) AS Total_Compra, SUM(Credito) AS Neto_Factura, 0 AS ValorIngresado, 0 as ValorMayorPagar, 0 as ValorDescuento, (SUM(Credito)-FC.Pagado) AS Por_Pagar, FC.Pagado, 'Notas Contables' AS Tipo_Factura FROM Cuenta_Nota_Contable CNC INNER JOIN Nota_Contable NC ON NC.Id_Nota_Contable = CNC.Id_Nota_Contable LEFT JOIN (SELECT Id_Factura, Factura, SUM(Valor) AS Pagado FROM Factura_Comprobante FC INNER JOIN Comprobante C ON FC.Id_Comprobante = C.Id_Comprobante WHERE C.Tipo = 'Egreso' GROUP BY FC.Id_Factura) FC ON NC.Id_Nota_Contable = FC.Id_Factura AND NC.Documento = FC.Factura WHERE NC.Beneficiario = $id AND CNC.Debito = 0 AND NC.Egreso = 'No'
)
UNION(
   SELECT
   0 AS Id_Plan_Cuentas,
   'NULL' AS Acta_Recepcion, 
   F.Codigo,
   F.Id_Devolucion_Compra,
   (SELECT 
           IFNULL(SUM((PAR.Cantidad * PAR.Costo)),
                       0)
       FROM
           Producto_Devolucion_Compra PAR
       WHERE
           PAR.Id_Devolucion_Compra = F.Id_Devolucion_Compra
               AND PAR.Impuesto = 0) AS Exenta,
   (SELECT 
           IFNULL(SUM((PAR.Cantidad * PAR.Costo)),
                       0)
       FROM
           Producto_Devolucion_Compra PAR
       WHERE
           PAR.Id_Devolucion_Compra = F.Id_Devolucion_Compra
               AND PAR.Impuesto != 0) AS Gravada,
   (SELECT 
           IFNULL(SUM((PAR.Cantidad * PAR.Costo * (PAR.Impuesto / 100))),
                       0)
       FROM
           Producto_Devolucion_Compra PAR
       WHERE
           PAR.Id_Devolucion_Compra = F.Id_Devolucion_Compra
               AND PAR.Impuesto != 0) AS Iva,
   ((SELECT 
           IFNULL(SUM((PAR.Cantidad * PAR.Costo)),
                       0)
       FROM
           Producto_Devolucion_Compra PAR
       WHERE
           PAR.Id_Devolucion_Compra = F.Id_Devolucion_Compra
               AND PAR.Impuesto = 0) + (SELECT 
           IFNULL(SUM((PAR.Cantidad * PAR.Costo)),
                       0)
       FROM
           Producto_Devolucion_Compra PAR
       WHERE
           PAR.Id_Devolucion_Compra = F.Id_Devolucion_Compra
               AND PAR.Impuesto != 0)) AS Total_Compra,
   ((SELECT 
           IFNULL(SUM((PAR.Cantidad * PAR.Costo)),
                       0)
       FROM
           Producto_Devolucion_Compra PAR
       WHERE
           PAR.Id_Devolucion_Compra = F.Id_Devolucion_Compra
               AND PAR.Impuesto = 0) + (SELECT 
           IFNULL(SUM((PAR.Cantidad * PAR.Costo)),
                       0)
       FROM
           Producto_Devolucion_Compra PAR
       WHERE
           PAR.Id_Devolucion_Compra = F.Id_Devolucion_Compra
               AND PAR.Impuesto != 0) + (SELECT 
           IFNULL(SUM((PAR.Cantidad * PAR.Costo) * (PAR.Impuesto / 100)),
                       0)
       FROM
           Producto_Devolucion_Compra PAR
       WHERE
           PAR.Id_Devolucion_Compra = F.Id_Devolucion_Compra
               AND PAR.Impuesto != 0)) AS Neto_Factura,

               0 as ValorIngresado,0 as ValorMayorPagar, 0 as ValorDescuento,

               IF(FC.Pagado IS NOT NULL, ((SELECT IFNULL(SUM((PAR.Cantidad*PAR.Costo)),0)
               FROM Producto_Devolucion_Compra PAR
               WHERE PAR.Id_Devolucion_Compra = F.Id_Devolucion_Compra AND PAR.Impuesto=0)+ (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Costo)),0)
               FROM Producto_Devolucion_Compra PAR
               WHERE PAR.Id_Devolucion_Compra = F.Id_Devolucion_Compra AND PAR.Impuesto!=0)+ (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Costo)*(PAR.Impuesto/100)),0)
               FROM Producto_Devolucion_Compra PAR
               WHERE PAR.Id_Devolucion_Compra = F.Id_Devolucion_Compra AND PAR.Impuesto!=0)) - FC.Pagado, ((SELECT IFNULL(SUM((PAR.Cantidad*PAR.Costo)),0)
               FROM Producto_Devolucion_Compra PAR
               WHERE PAR.Id_Devolucion_Compra = F.Id_Devolucion_Compra AND PAR.Impuesto=0)+ (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Costo)),0)
               FROM Producto_Devolucion_Compra PAR
               WHERE PAR.Id_Devolucion_Compra = F.Id_Devolucion_Compra AND PAR.Impuesto!=0)+ (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Costo)*(PAR.Impuesto/100)),0)
               FROM Producto_Devolucion_Compra PAR
               WHERE PAR.Id_Devolucion_Compra = F.Id_Devolucion_Compra AND PAR.Impuesto!=0))) AS Por_Pagar,

               IFNULL(FC.Pagado, 0) AS Pagado,
               'Devoluciones Compras' AS Tipo_Factura
FROM
   Devolucion_Compra F
LEFT JOIN (SELECT Id_Factura, Factura, SUM(Valor) AS Pagado FROM Factura_Comprobante FC INNER JOIN Comprobante C ON FC.Id_Comprobante = C.Id_Comprobante WHERE C.Tipo = 'Egreso' GROUP BY FC.Id_Factura) FC ON F.Id_Devolucion_Compra = FC.Id_Factura AND F.Codigo = FC.Factura
WHERE
       F.Id_Proveedor = ".$id."
GROUP BY F.Id_Devolucion_Compra
)
UNION ALL(

   SELECT
   0 AS Id_Plan_Cuenta, 'NULL' AS Id_Acta_Recepcion, FP.Factura, FP.Id_Facturas_Proveedor_Mantis AS Id_Factura,
   0 AS Exenta,
   0 AS Gravada,
   0 AS Iva,
   FP.Saldo AS Total_Compra,
   FP.Saldo AS Neto_Factura,
   0 AS ValorIngresado, 0 as ValorMayorPagar, 0 as ValorDescuento,
   IF(FC.Pagado IS NOT NULL, FP.Saldo - FC.Pagado, FP.Saldo) AS Por_Pagar,
   IFNULL(FC.Pagado, 0) AS Pagado,
   'Facturas Proveedor Mantis' AS Tipo_Factura
   FROM
   Facturas_Proveedor_Mantis FP
   INNER JOIN Proveedor P
   ON FP.Nit_Proveedor = P.Id_Proveedor
   LEFT JOIN (SELECT Id_Factura, Factura, SUM(Valor) AS Pagado FROM Factura_Comprobante FC INNER JOIN Comprobante C ON FC.Id_Comprobante = C.Id_Comprobante WHERE C.Tipo = 'Egreso' GROUP BY FC.Id_Factura) FC ON FP.Id_Facturas_Proveedor_Mantis = FC.Id_Factura AND FP.Factura = FC.Factura
   WHERE FP.Nit_Proveedor = $id AND FP.Estado = 'Pendiente'
)
";

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado = $oCon->getData();
unset($oCon);

$i=-1;
foreach ($resultado  as $value) {$i++;
   $resultado[$i]['RetencionesFacturas']=[];
   $resultado[$i]['DescuentosFactura']=[];
}


$datos['Facturas']=$resultado;


echo json_encode($datos);


?>