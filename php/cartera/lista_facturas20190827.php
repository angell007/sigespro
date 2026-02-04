<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = "(SELECT F.Codigo, F.Fecha_Documento, F.Condicion_Pago, IF(F.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(F.Fecha_Documento)) > F.Condicion_Pago, DATEDIFF(CURDATE(), DATE(F.Fecha_Documento)) - F.Condicion_Pago, 0), 0) AS Dias_Mora, DATE_ADD(F.Fecha_Documento, INTERVAL F.Condicion_Pago DAY) AS Fecha_Vencimiento, F.Id_Factura_Venta AS Id_Factura,  (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)),0)
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
WHERE PAR.Id_Factura_Venta = F.Id_Factura_Venta AND PAR.Impuesto!=0))AS Total_Compra,((SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)),0)
FROM Producto_Factura_Venta PAR
WHERE PAR.Id_Factura_Venta = F.Id_Factura_Venta AND PAR.Impuesto=0)+ (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)),0)
FROM Producto_Factura_Venta PAR
WHERE PAR.Id_Factura_Venta = F.Id_Factura_Venta AND PAR.Impuesto!=0)+ (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)*(PAR.Impuesto/100)),0)
FROM Producto_Factura_Venta PAR
WHERE PAR.Id_Factura_Venta = F.Id_Factura_Venta AND PAR.Impuesto!=0) - (SELECT IFNULL(SUM(PNC.Cantidad*PNC.Precio_Venta*(1+(PNC.Impuesto/100))),0) FROM Producto_Nota_Credito PNC INNER JOIN Nota_Credito NC ON PNC.Id_Nota_Credito = NC.Id_Nota_Credito WHERE NC.Id_Factura = F.Id_Factura_Venta) /* DEVOLUCION */) as Neto_Factura
FROM Factura_Venta F
INNER JOIN Producto_Factura_Venta PFV
ON PFV.Id_Factura_Venta=F.Id_Factura_Venta
WHERE F.Estado='Pendiente' AND YEAR(F.Fecha_Documento) >= 2019 AND F.Id_Cliente= ".$id."
GROUP BY F.Id_Factura_Venta)
UNION (
    SELECT
FCM.Factura,
FCM.Fecha_Factura,
C.Condicion_Pago,
IF(C.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(FCM.Fecha_Factura)) > C.Condicion_Pago, DATEDIFF(CURDATE(), DATE(FCM.Fecha_Factura)) - C.Condicion_Pago, 0), 0) AS Dias_Mora,
DATE_ADD(FCM.Fecha_Factura, INTERVAL C.Condicion_Pago DAY) AS Fecha_Vencimiento,
FCM.Id_Facturas_Cliente_Mantis,
0 AS Exenta,
0 AS Gravada,
0 AS Iva,
FCM.Saldo AS Total_Compra,
FCM.Saldo AS Neto_Factura
FROM
Facturas_Cliente_Mantis FCM
INNER JOIN Cliente C
ON FCM.Nit_Cliente = C.Id_Cliente
WHERE FCM.Estado = 'Pendiente'
AND FCM.Nit_Cliente = $id
)
UNION(
    SELECT 
    F.Codigo,
    F.Fecha,
    '' AS Condicion_Pago,
    '' AS Dias_Mora,
    '' AS Fecha_Vencimiento,
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
                AND PAR.Impuesto != 0)) AS Neto_Factura
FROM
    Nota_Credito F
WHERE
    F.Estado = 'Aprobada' AND YEAR(F.Fecha) >= 2019
        AND F.Id_Cliente = ".$id."
GROUP BY F.Id_Nota_Credito
)
UNION(
    SELECT
    FT.Codigo,
    FT.Fecha_Documento,
    0 AS Condicion_Pago,
    0 AS Dias_Mora,
    '' AS Fecha_Vencimiento,
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
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto=0),0)) AS Total_Venta,

    ((IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto!=0),0) + IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto=0),0)) + IFNULL((SELECT ROUND(SUM((PF.Cantidad*PF.Precio)*(19/100)),2)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto!=0),0) - IFNULL((SELECT SUM(PF.Cantidad*PF.Descuento)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura),0) - FT.Cuota) AS Neto_Factura

    FROM Factura FT
    WHERE FT.Estado_Factura = 'Sin Cancelar' AND YEAR(FT.Fecha_Documento) >= 2019 AND FT.Id_Cliente = $id
)
UNION(
    SELECT
    FC.Codigo,
    FC.Fecha_Documento,
    0 AS Condicion_Pago,
    0 AS Dias_Mora,
    '' AS Fecha_Vencimiento,
    FC.Id_Factura_Capita AS Id_Factura,

    IFNULL((SELECT SUM(DFC.Cantidad*DFC.Precio)
    FROM Descripcion_Factura_Capita DFC
    WHERE DFC.Id_Factura_Capita = FC.Id_Factura_Capita),0) as Exenta,

    0 AS Gravada,
    0 AS Iva,

    IFNULL((SELECT SUM(DFC.Cantidad*DFC.Precio)
    FROM Descripcion_Factura_Capita DFC
    WHERE DFC.Id_Factura_Capita = FC.Id_Factura_Capita),0) as Total_Venta,

    (IFNULL((SELECT SUM(DFC.Cantidad*DFC.Precio)
    FROM Descripcion_Factura_Capita DFC
    WHERE DFC.Id_Factura_Capita = FC.Id_Factura_Capita),0) - FC.Cuota_Moderadora) AS Neto_Factura

    FROM
    Factura_Capita FC
    WHERE Estado_Factura = 'Sin Cancelar' AND YEAR(FC.Fecha_Documento) >= 2019 AND FC.Id_Cliente = $id
)";



$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado = $oCon->getData();
unset($oCon);
$i=-1;
foreach ($resultado as $value) {$i++;
    $resultado[$i]['RetencionesFacturas']=[];
    $resultado[$i]['Condicion_Pago']= (INT) $value['Condicion_Pago'];
    $resultado[$i]['Dias_Mora']= (INT) $value['Dias_Mora'];
}

$query='SELECT * FROM Cliente WHERE Id_Cliente='.$id;
$oCon= new consulta();
$oCon->setQuery($query);
$cliente = $oCon->getData();
unset($oCon);

$datos['Facturas']=$resultado;
$datos['Cliente']=$cliente;


echo json_encode($datos);


?>