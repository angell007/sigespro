<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = "
SELECT
r.*
FROM
(

   (SELECT
FAR.Factura,FAR.Fecha_Factura,FAR.Archivo_Factura, DATE_ADD(FAR.Fecha_Factura, INTERVAL P.Condicion_Pago DAY) AS Fecha_Vencimiento, P.Condicion_Pago,
FAR.Id_Factura_Acta_Recepcion AS Id_Factura,

IF(P.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(FAR.Fecha_Factura)) > P.Condicion_Pago, DATEDIFF(CURDATE(), DATE(FAR.Fecha_Factura)) - P.Condicion_Pago, 0), 0) AS Dias_Mora,

(SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio)),0)
FROM Producto_Acta_Recepcion PAR2
WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto=0) as Exenta,

(SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio)),0)
FROM Producto_Acta_Recepcion PAR2
WHERE PAR2.Factura = FAR.Factura AND PAR2.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion AND PAR2.Impuesto!=0) as Gravada,

(SELECT IFNULL(SUM((PAR2.Cantidad*PAR2.Precio*(PAR2.Impuesto/100))),0)
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

 0 AS ValorIngresado, 0 as ValorMayorPagar, 0 as ValorDescuento
 
FROM
Factura_Acta_Recepcion FAR
INNER JOIN Acta_Recepcion AR
ON FAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion
INNER JOIN Proveedor P
ON AR.Id_Proveedor = P.Id_Proveedor
INNER JOIN Producto_Acta_Recepcion PAR
ON PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion
WHERE AR.Id_Proveedor = $id AND FAR.Estado = 'Pendiente' AND YEAR(AR.Fecha_Creacion) >= 2019
GROUP BY FAR.Id_Acta_Recepcion, FAR.Factura)
UNION(
   SELECT 
   F.Codigo,
   F.Fecha,
   '' AS Archivo_Factura,
   '' AS Fecha_Vencimiento,
   0 AS Condicion_Pago,
   F.Id_Devolucion_Compra,
   0 AS Dias_Mora,
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
               0 AS ValorIngresado, 0 as ValorMayorPagar, 0 as ValorDescuento
FROM
   Devolucion_Compra F
WHERE
   YEAR(F.Fecha) >= 2019
       AND F.Id_Proveedor = ".$id."
)
UNION ALL(

   SELECT
   FP.Factura,FP.Fecha_Factura, '' AS Archivo_Factura, DATE_ADD(FP.Fecha_Factura, INTERVAL P.Condicion_Pago DAY) AS Fecha_Vencimiento, P.Condicion_Pago,
   FP.Id_Facturas_Proveedor_Mantis AS Id_Factura,

   IF(P.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(FP.Fecha_Factura)) > P.Condicion_Pago, DATEDIFF(CURDATE(), DATE(FP.Fecha_Factura)) - P.Condicion_Pago, 0), 0) AS Dias_Mora,
   0 AS Exenta,
   0 AS Gravada,
   0 AS Iva,
   FP.Saldo AS Total_Compra,
   FP.Saldo AS Neto_Factura,
   0 AS ValorIngresado, 0 as ValorMayorPagar, 0 as ValorDescuento
   FROM
   Facturas_Proveedor_Mantis FP
   INNER JOIN Proveedor P
   ON FP.Nit_Proveedor = P.Id_Proveedor
   WHERE FP.Nit_Proveedor = $id AND FP.Estado = 'Pendiente'
)
) r
ORDER BY r.Fecha_Factura DESC
";

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado = $oCon->getData();
unset($oCon);

$i=-1;
foreach ($resultado  as $value) {$i++;
   $resultado[$i]['RetencionesFacturas']=[];
}

$query='SELECT * FROM Proveedor WHERE Id_Proveedor='.$id;
$oCon= new consulta();
$oCon->setQuery($query);
$proveedor = $oCon->getData();
unset($oCon);

$datos['Facturas']=$resultado;
$datos['Proveedor']=$proveedor;


echo json_encode($datos);


?>