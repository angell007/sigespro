<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

## SE CAMBIÓ LA LOGICA PARA OBTENER LA COLUMNA PAGADO

$query = "(SELECT F.Codigo, F.Id_Factura_Venta AS Id_Factura, (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)),0)
FROM Producto_Factura_Venta PAR
WHERE PAR.Id_Factura_Venta = F.Id_Factura_Venta AND PAR.Impuesto=0) as Exenta,

(SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)),0)
FROM Producto_Factura_Venta PAR
WHERE PAR.Id_Factura_Venta = F.Id_Factura_Venta AND PAR.Impuesto!=0) as Gravada,


(SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta*(PAR.Impuesto/100))),0)
    FROM Producto_Factura_Venta PAR INNER JOIN Producto PDT ON PDT.Id_Producto = PAR.Id_Producto
    WHERE PAR.Id_Factura_Venta = F.Id_Factura_Venta) as Iva, 
    
((SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)),0)
FROM Producto_Factura_Venta PAR
WHERE PAR.Id_Factura_Venta = F.Id_Factura_Venta AND PAR.Impuesto=0)+ (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)),0)
FROM Producto_Factura_Venta PAR
WHERE PAR.Id_Factura_Venta = F.Id_Factura_Venta AND PAR.Impuesto!=0))AS Total_Compra,

((SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)),0)
FROM Producto_Factura_Venta PAR
WHERE PAR.Id_Factura_Venta = F.Id_Factura_Venta AND PAR.Impuesto=0)+ (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)),0)
FROM Producto_Factura_Venta PAR
WHERE PAR.Id_Factura_Venta = F.Id_Factura_Venta AND PAR.Impuesto!=0)+ (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta*(PAR.Impuesto/100))),0)
    FROM Producto_Factura_Venta PAR INNER JOIN Producto PDT ON PDT.Id_Producto = PAR.Id_Producto
    WHERE PAR.Id_Factura_Venta = F.Id_Factura_Venta)) as Neto_Factura, 

0 as ValorIngresado,0 as ValorMayorPagar, 0 as ValorDescuento,

((SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)),0)
FROM Producto_Factura_Venta PAR
WHERE PAR.Id_Factura_Venta = F.Id_Factura_Venta AND PAR.Impuesto=0)+ (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)),0)
FROM Producto_Factura_Venta PAR
WHERE PAR.Id_Factura_Venta = F.Id_Factura_Venta AND PAR.Impuesto!=0)+ (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta*(PAR.Impuesto/100))),0)
    FROM Producto_Factura_Venta PAR INNER JOIN Producto PDT ON PDT.Id_Producto = PAR.Id_Producto
    WHERE PAR.Id_Factura_Venta = F.Id_Factura_Venta) - (

    SELECT 
    IFNULL(SUM(MC.Debe)-(SUM(MC.Debe)-SUM(MC.Haber)),0)
    FROM
    Movimiento_Contable MC
    WHERE
        MC.Nit = F.Id_CLiente AND MC.Documento = F.Codigo AND MC.Estado != 'Anulado' AND MC.Id_Plan_Cuenta = 57

)) AS Por_Pagar,

(

    SELECT 
    IFNULL(SUM(MC.Debe)-(SUM(MC.Debe)-SUM(MC.Haber)),0)
    FROM
    Movimiento_Contable MC
    WHERE
        MC.Nit = F.Id_Cliente AND MC.Documento = F.Codigo AND MC.Estado != 'Anulado' AND MC.Id_Plan_Cuenta = 57

) AS Pagado

FROM Factura_Venta F
INNER JOIN Cliente C ON F.Id_Cliente = C.Id_Cliente
WHERE F.Estado='Pendiente' AND YEAR(F.Fecha_Documento) >= 2019 AND F.Id_Cliente= ".$id."
GROUP BY F.Id_Factura_Venta)
UNION (
    SELECT
FCM.Factura,
FCM.Id_Facturas_Cliente_Mantis AS Id_Factura,
0 AS Exenta,
0 AS Gravada,
0 AS Iva,
FCM.Saldo AS Total_Compra,
FCM.Saldo AS Neto_Factura,
0 as ValorIngresado,0 as ValorMayorPagar, 0 as ValorDescuento,
FCM.Saldo - (

    SELECT 
    IFNULL(SUM(MC.Debe)-(SUM(MC.Debe)-SUM(MC.Haber)),0)
    FROM
    Movimiento_Contable MC
    WHERE
        MC.Nit = FCM.Nit_Cliente AND MC.Documento = FCM.Factura AND MC.Estado != 'Anulado' AND MC.Id_Plan_Cuenta = 57

) AS Por_Pagar,
(

    SELECT 
    IFNULL(SUM(MC.Debe)-(SUM(MC.Debe)-SUM(MC.Haber)),0)
    FROM
    Movimiento_Contable MC
    WHERE
        MC.Nit = FCM.Nit_Cliente AND MC.Documento = FCM.Factura AND MC.Estado != 'Anulado' AND MC.Id_Plan_Cuenta = 57

)  AS Pagado
FROM
Facturas_Cliente_Mantis FCM
WHERE FCM.Estado = 'Pendiente'
AND FCM.Nit_Cliente = $id
)
UNION(
    SELECT 
    F.Codigo,
    F.Id_Nota_Credito,
    (SELECT 
            IFNULL(SUM((PAR.Cantidad * PAR.Precio_Venta)),
                        0)
        FROM
            Producto_Nota_Credito PAR
        WHERE
            PAR.Id_Nota_Credito = F.Id_Nota_Credito
                AND PAR.Impuesto = 0) AS Exenta,
    (SELECT 
            IFNULL(SUM((PAR.Cantidad * PAR.Precio_Venta)),
                        0)
        FROM
            Producto_Nota_Credito PAR
        WHERE
            PAR.Id_Nota_Credito = F.Id_Nota_Credito
                AND PAR.Impuesto != 0) AS Gravada,
    (SELECT 
            IFNULL(SUM((PAR.Cantidad * PAR.Precio_Venta * (PAR.Impuesto / 100))),
                        0)
        FROM
            Producto_Nota_Credito PAR
        WHERE
            PAR.Id_Nota_Credito = F.Id_Nota_Credito
                AND PAR.Impuesto != 0) AS Iva,
    ((SELECT 
            IFNULL(SUM((PAR.Cantidad * PAR.Precio_Venta)),
                        0)
        FROM
            Producto_Nota_Credito PAR
        WHERE
            PAR.Id_Nota_Credito = F.Id_Nota_Credito
                AND PAR.Impuesto = 0) + (SELECT 
            IFNULL(SUM((PAR.Cantidad * PAR.Precio_Venta)),
                        0)
        FROM
            Producto_Nota_Credito PAR
        WHERE
            PAR.Id_Nota_Credito = F.Id_Nota_Credito
                AND PAR.Impuesto != 0)) AS Total_Compra,
    ((SELECT 
            IFNULL(SUM((PAR.Cantidad * PAR.Precio_Venta)),
                        0)
        FROM
            Producto_Nota_Credito PAR
        WHERE
            PAR.Id_Nota_Credito = F.Id_Nota_Credito
                AND PAR.Impuesto = 0) + (SELECT 
            IFNULL(SUM((PAR.Cantidad * PAR.Precio_Venta)),
                        0)
        FROM
            Producto_Nota_Credito PAR
        WHERE
            PAR.Id_Nota_Credito = F.Id_Nota_Credito
                AND PAR.Impuesto != 0) + (SELECT 
            IFNULL(SUM((PAR.Cantidad * PAR.Precio_Venta) * (PAR.Impuesto / 100)),
                        0)
        FROM
            Producto_Nota_Credito PAR
        WHERE
            PAR.Id_Nota_Credito = F.Id_Nota_Credito
                AND PAR.Impuesto != 0)) AS Neto_Factura,

                0 as ValorIngresado,0 as ValorMayorPagar, 0 as ValorDescuento,

                ((SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)),0)
                FROM Producto_Nota_Credito PAR
                WHERE PAR.Id_Nota_Credito = F.Id_Nota_Credito AND PAR.Impuesto=0)+ (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)),0)
                FROM Producto_Nota_Credito PAR
                WHERE PAR.Id_Nota_Credito = F.Id_Nota_Credito AND PAR.Impuesto!=0)+ (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)*(PAR.Impuesto/100)),0)
                FROM Producto_Nota_Credito PAR
                WHERE PAR.Id_Nota_Credito = F.Id_Nota_Credito AND PAR.Impuesto!=0)) - (

                    SELECT 
                    IFNULL(SUM(MC.Haber)-(SUM(MC.Haber)-SUM(MC.Debe)),0)
                    FROM
                    Movimiento_Contable MC
                    WHERE
                        MC.Nit = F.Id_Cliente AND MC.Documento = F.Codigo AND MC.Estado != 'Anulado' AND MC.Id_Plan_Cuenta = 57
                
                ) AS Por_Pagar,

                (

                    SELECT 
                    IFNULL(SUM(MC.Haber)-(SUM(MC.Haber)-SUM(MC.Debe)),0)
                    FROM
                    Movimiento_Contable MC
                    WHERE
                        MC.Nit = F.Id_Cliente AND MC.Documento = F.Codigo AND MC.Estado != 'Anulado' AND MC.Id_Plan_Cuenta = 57
                
                ) AS Pagado
FROM
    Nota_Credito F
WHERE
    F.Estado != 'Rechazada' AND   F.Estado != 'Pendiente' 
        AND F.Id_Cliente = ".$id."
GROUP BY F.Id_Nota_Credito
)
UNION (
    SELECT
    FT.Codigo,
    FT.Id_Factura,

    IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto=0),0) as Exenta,

    IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto!=0),0) as Gravada,

    IFNULL((SELECT ROUND(SUM((PF.Cantidad*PF.Precio)*(19/100)),2)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto!=0),0) as Iva,
    

    (IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto!=0),0) + IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto=0),0) - IFNULL((SELECT SUM(PF.Cantidad*PF.Descuento)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura),0) - FT.Cuota) AS Total_Venta,

    ((IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto!=0),0) + IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto=0),0)) + IFNULL((SELECT ROUND(SUM((PF.Cantidad*PF.Precio)*(19/100)),2)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto!=0),0) - IFNULL((SELECT SUM(PF.Cantidad*PF.Descuento)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura),0) - FT.Cuota) AS Neto_Factura,

    0 ValorIngresado,0 as ValorMayorPagar, 0 as ValorDescuento,

    IF(FC.Pagado IS NOT NULL, ((IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto!=0),0) + IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto=0),0)) + IFNULL((SELECT ROUND(SUM((PF.Cantidad*PF.Precio)*(19/100)),2)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto!=0),0)) - IFNULL((SELECT SUM(PF.Cantidad*PF.Descuento)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura),0) - FT.Cuota - FC.Pagado, ((IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto!=0),0) + IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto=0),0)) + IFNULL((SELECT ROUND(SUM((PF.Cantidad*PF.Precio)*(19/100)),2)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto!=0),0) - IFNULL((SELECT SUM(PF.Cantidad*PF.Descuento)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura),0) - FT.Cuota)) AS Por_Pagar,

    IFNULL(FC.Pagado, 0) AS Pagado

    FROM Factura FT
    LEFT JOIN (SELECT Id_Factura, Factura, SUM(Valor) AS Pagado FROM Factura_Comprobante FC INNER JOIN Comprobante C ON FC.Id_Comprobante = C.Id_Comprobante WHERE C.Tipo = 'Ingreso' AND C.Estado = 'Activa' GROUP BY FC.Id_Factura) FC ON FT.Id_Factura = FC.Id_Factura AND FT.Codigo = FC.Factura
    WHERE FT.Estado_Factura = 'Sin Cancelar' AND YEAR(FT.Fecha_Documento) >= 2019 AND FT.Id_Cliente = $id
)
UNION(
    SELECT
    F.Codigo,
    F.Id_Factura_Capita AS Id_Factura,

    IFNULL((SELECT SUM(DFC.Cantidad*DFC.Precio)
    FROM Descripcion_Factura_Capita DFC
    WHERE DFC.Id_Factura_Capita = F.Id_Factura_Capita),0) as Exenta,

    0 AS Gravada,
    0 AS Iva,

    IFNULL((SELECT SUM(DFC.Cantidad*DFC.Precio)
    FROM Descripcion_Factura_Capita DFC
    WHERE DFC.Id_Factura_Capita = F.Id_Factura_Capita),0) as Total_Venta,

    (IFNULL((SELECT SUM(DFC.Cantidad*DFC.Precio)
    FROM Descripcion_Factura_Capita DFC
    WHERE DFC.Id_Factura_Capita = F.Id_Factura_Capita),0) - F.Cuota_Moderadora) AS Neto_Factura,

    0 AS ValorIngresado,0 as ValorMayorPagar, 0 as ValorDescuento,

    IF(FC.Pagado IS NOT NULL, IFNULL((SELECT SUM(DFC.Cantidad*DFC.Precio)
    FROM Descripcion_Factura_Capita DFC
    WHERE DFC.Id_Factura_Capita = F.Id_Factura_Capita),0) - F.Cuota_Moderadora - FC.Pagado, IFNULL((SELECT SUM(DFC.Cantidad*DFC.Precio)
    FROM Descripcion_Factura_Capita DFC
    WHERE DFC.Id_Factura_Capita = F.Id_Factura_Capita),0)) as Por_Pagar,

    IFNULL(FC.Pagado, 0) AS Pagado

    FROM
    Factura_Capita F
    LEFT JOIN (SELECT Id_Factura, Factura, SUM(Valor) AS Pagado FROM Factura_Comprobante FC INNER JOIN Comprobante C ON FC.Id_Comprobante = C.Id_Comprobante WHERE C.Tipo = 'Ingreso' AND C.Estado = 'Activa' GROUP BY FC.Id_Factura) FC ON F.Id_Factura_Capita = FC.Id_Factura AND F.Codigo = FC.Factura
    WHERE F.Estado_Factura = 'Sin Cancelar' AND YEAR(F.Fecha_Documento) >= 2019 AND F.Id_Cliente = $id
)";


## COMENTADO 24/07/2019 - KENDRY

/* $query = "(SELECT F.Codigo, F.Id_Factura_Venta AS Id_Factura, (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)),0)
FROM Producto_Factura_Venta PAR
WHERE PAR.Id_Factura_Venta = F.Id_Factura_Venta AND PAR.Impuesto=0) as Exenta,

(SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)),0)
FROM Producto_Factura_Venta PAR
WHERE PAR.Id_Factura_Venta = F.Id_Factura_Venta AND PAR.Impuesto!=0) as Gravada,

(SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta*(PAR.Impuesto/100))),0)
FROM Producto_Factura_Venta PAR
WHERE PAR.Id_Factura_Venta = F.Id_Factura_Venta AND PAR.Impuesto!=0) as Iva,

((SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)),0)
FROM Producto_Factura_Venta PAR
WHERE PAR.Id_Factura_Venta = F.Id_Factura_Venta AND PAR.Impuesto=0)+ (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)),0)
FROM Producto_Factura_Venta PAR
WHERE PAR.Id_Factura_Venta = F.Id_Factura_Venta AND PAR.Impuesto!=0))AS Total_Compra,

((SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)),0)
FROM Producto_Factura_Venta PAR
WHERE PAR.Id_Factura_Venta = F.Id_Factura_Venta AND PAR.Impuesto=0)+ (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)),0)
FROM Producto_Factura_Venta PAR
WHERE PAR.Id_Factura_Venta = F.Id_Factura_Venta AND PAR.Impuesto!=0)+ (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)*(PAR.Impuesto/100)),0)
FROM Producto_Factura_Venta PAR
WHERE PAR.Id_Factura_Venta = F.Id_Factura_Venta AND PAR.Impuesto!=0)) as Neto_Factura, 

0 as ValorIngresado,0 as ValorMayorPagar, 0 as ValorDescuento,

IF(FC.Pagado IS NOT NULL, ((SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)),0)
FROM Producto_Factura_Venta PAR
WHERE PAR.Id_Factura_Venta = F.Id_Factura_Venta AND PAR.Impuesto=0)+ (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)),0)
FROM Producto_Factura_Venta PAR
WHERE PAR.Id_Factura_Venta = F.Id_Factura_Venta AND PAR.Impuesto!=0)+ (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)*(PAR.Impuesto/100)),0)
FROM Producto_Factura_Venta PAR
WHERE PAR.Id_Factura_Venta = F.Id_Factura_Venta AND PAR.Impuesto!=0)) - FC.Pagado, ((SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)),0)
FROM Producto_Factura_Venta PAR
WHERE PAR.Id_Factura_Venta = F.Id_Factura_Venta AND PAR.Impuesto=0)+ (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)),0)
FROM Producto_Factura_Venta PAR
WHERE PAR.Id_Factura_Venta = F.Id_Factura_Venta AND PAR.Impuesto!=0)+ (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)*(PAR.Impuesto/100)),0)
FROM Producto_Factura_Venta PAR
WHERE PAR.Id_Factura_Venta = F.Id_Factura_Venta AND PAR.Impuesto!=0))) AS Por_Pagar,

IFNULL(FC.Pagado, 0) AS Pagado

FROM Factura_Venta F
LEFT JOIN (SELECT Id_Factura, Factura, SUM(Valor) AS Pagado FROM Factura_Comprobante FC INNER JOIN Comprobante C ON FC.Id_Comprobante = C.Id_Comprobante WHERE C.Tipo = 'Ingreso' AND C.Estado = 'Activa' GROUP BY FC.Id_Factura) FC ON F.Id_Factura_Venta = FC.Id_Factura AND F.Codigo = FC.Factura
WHERE F.Estado='Pendiente' AND YEAR(F.Fecha_Documento) >= 2019 AND F.Id_Cliente= ".$id."
GROUP BY F.Id_Factura_Venta)
UNION (
    SELECT
FCM.Factura,
FCM.Id_Facturas_Cliente_Mantis AS Id_Factura,
0 AS Exenta,
0 AS Gravada,
0 AS Iva,
FCM.Saldo AS Total_Compra,
FCM.Saldo AS Neto_Factura,
0 as ValorIngresado,0 as ValorMayorPagar, 0 as ValorDescuento,
IF(FC.Pagado IS NOT NULL, FCM.Saldo - FC.Pagado, FCM.Saldo) AS Por_Pagar,
IFNULL(FC.Pagado, 0) AS Pagado
FROM
Facturas_Cliente_Mantis FCM
LEFT JOIN (SELECT Id_Factura, Factura, SUM(Valor) AS Pagado FROM Factura_Comprobante FC INNER JOIN Comprobante C ON FC.Id_Comprobante = C.Id_Comprobante WHERE C.Tipo = 'Ingreso' AND C.Estado = 'Activa' GROUP BY FC.Id_Factura) FC ON FCM.Id_Facturas_Cliente_Mantis = FC.Id_Factura AND FCM.Factura = FC.Factura
WHERE FCM.Estado = 'Pendiente'
AND FCM.Nit_Cliente = $id
)
UNION(
    SELECT 
    F.Codigo,
    F.Id_Nota_Credito,
    (SELECT 
            IFNULL(SUM((PAR.Cantidad * PAR.Precio_Venta)),
                        0)
        FROM
            Producto_Nota_Credito PAR
        WHERE
            PAR.Id_Nota_Credito = F.Id_Nota_Credito
                AND PAR.Impuesto = 0) AS Exenta,
    (SELECT 
            IFNULL(SUM((PAR.Cantidad * PAR.Precio_Venta)),
                        0)
        FROM
            Producto_Nota_Credito PAR
        WHERE
            PAR.Id_Nota_Credito = F.Id_Nota_Credito
                AND PAR.Impuesto != 0) AS Gravada,
    (SELECT 
            IFNULL(SUM((PAR.Cantidad * PAR.Precio_Venta * (PAR.Impuesto / 100))),
                        0)
        FROM
            Producto_Nota_Credito PAR
        WHERE
            PAR.Id_Nota_Credito = F.Id_Nota_Credito
                AND PAR.Impuesto != 0) AS Iva,
    ((SELECT 
            IFNULL(SUM((PAR.Cantidad * PAR.Precio_Venta)),
                        0)
        FROM
            Producto_Nota_Credito PAR
        WHERE
            PAR.Id_Nota_Credito = F.Id_Nota_Credito
                AND PAR.Impuesto = 0) + (SELECT 
            IFNULL(SUM((PAR.Cantidad * PAR.Precio_Venta)),
                        0)
        FROM
            Producto_Nota_Credito PAR
        WHERE
            PAR.Id_Nota_Credito = F.Id_Nota_Credito
                AND PAR.Impuesto != 0)) AS Total_Compra,
    ((SELECT 
            IFNULL(SUM((PAR.Cantidad * PAR.Precio_Venta)),
                        0)
        FROM
            Producto_Nota_Credito PAR
        WHERE
            PAR.Id_Nota_Credito = F.Id_Nota_Credito
                AND PAR.Impuesto = 0) + (SELECT 
            IFNULL(SUM((PAR.Cantidad * PAR.Precio_Venta)),
                        0)
        FROM
            Producto_Nota_Credito PAR
        WHERE
            PAR.Id_Nota_Credito = F.Id_Nota_Credito
                AND PAR.Impuesto != 0) + (SELECT 
            IFNULL(SUM((PAR.Cantidad * PAR.Precio_Venta) * (PAR.Impuesto / 100)),
                        0)
        FROM
            Producto_Nota_Credito PAR
        WHERE
            PAR.Id_Nota_Credito = F.Id_Nota_Credito
                AND PAR.Impuesto != 0)) AS Neto_Factura,

                0 as ValorIngresado,0 as ValorMayorPagar, 0 as ValorDescuento,

                IF(FC.Pagado IS NOT NULL, ((SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)),0)
                FROM Producto_Nota_Credito PAR
                WHERE PAR.Id_Nota_Credito = F.Id_Nota_Credito AND PAR.Impuesto=0)+ (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)),0)
                FROM Producto_Nota_Credito PAR
                WHERE PAR.Id_Nota_Credito = F.Id_Nota_Credito AND PAR.Impuesto!=0)+ (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)*(PAR.Impuesto/100)),0)
                FROM Producto_Nota_Credito PAR
                WHERE PAR.Id_Nota_Credito = F.Id_Nota_Credito AND PAR.Impuesto!=0)) - FC.Pagado, ((SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)),0)
                FROM Producto_Nota_Credito PAR
                WHERE PAR.Id_Nota_Credito = F.Id_Nota_Credito AND PAR.Impuesto=0)+ (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)),0)
                FROM Producto_Nota_Credito PAR
                WHERE PAR.Id_Nota_Credito = F.Id_Nota_Credito AND PAR.Impuesto!=0)+ (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)*(PAR.Impuesto/100)),0)
                FROM Producto_Nota_Credito PAR
                WHERE PAR.Id_Nota_Credito = F.Id_Nota_Credito AND PAR.Impuesto!=0))) AS Por_Pagar,

                IFNULL(FC.Pagado, 0) AS Pagado
FROM
    Nota_Credito F
LEFT JOIN (SELECT Id_Factura, Factura, SUM(Valor) AS Pagado FROM Factura_Comprobante FC INNER JOIN Comprobante C ON FC.Id_Comprobante = C.Id_Comprobante WHERE C.Tipo = 'Ingreso' AND C.Estado = 'Activa' GROUP BY FC.Id_Factura) FC ON F.Id_Nota_Credito = FC.Id_Factura AND F.Codigo = FC.Factura
WHERE
    F.Estado = 'Aprobada'
        AND F.Id_Cliente = ".$id."
GROUP BY F.Id_Nota_Credito
)
UNION (
    SELECT
    FT.Codigo,
    FT.Id_Factura,

    IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto=0),0) as Exenta,

    IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto!=0),0) as Gravada,

    IFNULL((SELECT ROUND(SUM((PF.Cantidad*PF.Precio)*(19/100)),2)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto!=0),0) as Iva,
    

    (IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto!=0),0) + IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto=0),0) - IFNULL((SELECT SUM(PF.Cantidad*PF.Descuento)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura),0) - FT.Cuota) AS Total_Venta,

    ((IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto!=0),0) + IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto=0),0)) + IFNULL((SELECT ROUND(SUM((PF.Cantidad*PF.Precio)*(19/100)),2)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto!=0),0) - IFNULL((SELECT SUM(PF.Cantidad*PF.Descuento)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura),0) - FT.Cuota) AS Neto_Factura,

    0 ValorIngresado,0 as ValorMayorPagar, 0 as ValorDescuento,

    IF(FC.Pagado IS NOT NULL, ((IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto!=0),0) + IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto=0),0)) + IFNULL((SELECT ROUND(SUM((PF.Cantidad*PF.Precio)*(19/100)),2)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto!=0),0)) - IFNULL((SELECT SUM(PF.Cantidad*PF.Descuento)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura),0) - FT.Cuota - FC.Pagado, ((IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto!=0),0) + IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto=0),0)) + IFNULL((SELECT ROUND(SUM((PF.Cantidad*PF.Precio)*(19/100)),2)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto!=0),0) - IFNULL((SELECT SUM(PF.Cantidad*PF.Descuento)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura),0) - FT.Cuota)) AS Por_Pagar,

    IFNULL(FC.Pagado, 0) AS Pagado

    FROM Factura FT
    LEFT JOIN (SELECT Id_Factura, Factura, SUM(Valor) AS Pagado FROM Factura_Comprobante FC INNER JOIN Comprobante C ON FC.Id_Comprobante = C.Id_Comprobante WHERE C.Tipo = 'Ingreso' AND C.Estado = 'Activa' GROUP BY FC.Id_Factura) FC ON FT.Id_Factura = FC.Id_Factura AND FT.Codigo = FC.Factura
    WHERE FT.Estado_Factura = 'Sin Cancelar' AND YEAR(FT.Fecha_Documento) >= 2019 AND FT.Id_Cliente = $id
)
UNION(
    SELECT
    F.Codigo,
    F.Id_Factura_Capita AS Id_Factura,

    IFNULL((SELECT SUM(DFC.Cantidad*DFC.Precio)
    FROM Descripcion_Factura_Capita DFC
    WHERE DFC.Id_Factura_Capita = F.Id_Factura_Capita),0) as Exenta,

    0 AS Gravada,
    0 AS Iva,

    IFNULL((SELECT SUM(DFC.Cantidad*DFC.Precio)
    FROM Descripcion_Factura_Capita DFC
    WHERE DFC.Id_Factura_Capita = F.Id_Factura_Capita),0) as Total_Venta,

    (IFNULL((SELECT SUM(DFC.Cantidad*DFC.Precio)
    FROM Descripcion_Factura_Capita DFC
    WHERE DFC.Id_Factura_Capita = F.Id_Factura_Capita),0) - F.Cuota_Moderadora) AS Neto_Factura,

    0 AS ValorIngresado,0 as ValorMayorPagar, 0 as ValorDescuento,

    IF(FC.Pagado IS NOT NULL, IFNULL((SELECT SUM(DFC.Cantidad*DFC.Precio)
    FROM Descripcion_Factura_Capita DFC
    WHERE DFC.Id_Factura_Capita = F.Id_Factura_Capita),0) - F.Cuota_Moderadora - FC.Pagado, IFNULL((SELECT SUM(DFC.Cantidad*DFC.Precio)
    FROM Descripcion_Factura_Capita DFC
    WHERE DFC.Id_Factura_Capita = F.Id_Factura_Capita),0)) as Por_Pagar,

    IFNULL(FC.Pagado, 0) AS Pagado

    FROM
    Factura_Capita F
    LEFT JOIN (SELECT Id_Factura, Factura, SUM(Valor) AS Pagado FROM Factura_Comprobante FC INNER JOIN Comprobante C ON FC.Id_Comprobante = C.Id_Comprobante WHERE C.Tipo = 'Ingreso' AND C.Estado = 'Activa' GROUP BY FC.Id_Factura) FC ON F.Id_Factura_Capita = FC.Id_Factura AND F.Codigo = FC.Factura
    WHERE F.Estado_Factura = 'Sin Cancelar' AND YEAR(F.Fecha_Documento) >= 2019 AND F.Id_Cliente = $id
)"; */



$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado = $oCon->getData();

unset($oCon);
$i=-1;
foreach ($resultado as $value) {$i++;
    $resultado[$i]['RetencionesFacturas']=[];
    $resultado[$i]['DescuentosFactura']=[];
}

$datos['Facturas']=$resultado;

echo json_encode($datos);


?>