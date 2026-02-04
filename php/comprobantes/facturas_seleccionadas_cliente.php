<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
$cliente = ( isset( $_REQUEST['cliente'] ) ? $_REQUEST['cliente'] : '' );
$facturas = ( isset( $_REQUEST['facturas'] ) ? $_REQUEST['facturas'] : '' );
$facturas = explode(',', $facturas);

foreach ($facturas as $i => $value) {
    $facturas[$i] = '"' . $value . '"';
}

$facturas = implode(',', $facturas);

$query = "(SELECT F.Codigo, F.Id_Factura_Venta AS Id_Factura, (SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio_Venta)),0)
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
WHERE PAR.Id_Factura_Venta = F.Id_Factura_Venta AND PAR.Impuesto!=0)) as Neto_Factura, 
0 as ValorIngresado,
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
LEFT JOIN (SELECT Id_Factura, Factura, SUM(Valor) AS Pagado FROM Factura_Comprobante FC INNER JOIN Comprobante C ON FC.Id_Comprobante = C.Id_Comprobante WHERE C.Tipo = 'Ingreso' GROUP BY FC.Id_Factura) FC ON F.Id_Factura_Venta = FC.Id_Factura AND F.Codigo = FC.Factura
WHERE F.Estado='Pendiente' AND F.Codigo IN ($facturas)
GROUP BY F.Id_Factura_Venta)
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

                0 as ValorIngresado,

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
LEFT JOIN (SELECT Id_Factura, Factura, SUM(Valor) AS Pagado FROM Factura_Comprobante FC INNER JOIN Comprobante C ON FC.Id_Comprobante = C.Id_Comprobante WHERE C.Tipo = 'Ingreso' GROUP BY FC.Id_Factura) FC ON F.Id_Nota_Credito = FC.Id_Factura AND F.Codigo = FC.Factura
WHERE
    F.Estado = 'Aprobada'
    AND F.Codigo IN ($facturas)
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
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto=0),0)) AS Total_Venta,

    ((IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto!=0),0) + IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto=0),0)) + IFNULL((SELECT ROUND(SUM((PF.Cantidad*PF.Precio)*(19/100)),2)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto!=0),0)) AS Neto_Factura,

    0 ValorIngresado,

    IF(FC.Pagado IS NOT NULL, ((IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto!=0),0) + IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto=0),0)) + IFNULL((SELECT ROUND(SUM((PF.Cantidad*PF.Precio)*(19/100)),2)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto!=0),0)) - FC.Pagado, ((IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto!=0),0) + IFNULL((SELECT SUM(PF.Cantidad*PF.Precio)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto=0),0)) + IFNULL((SELECT ROUND(SUM((PF.Cantidad*PF.Precio)*(19/100)),2)
    FROM Producto_Factura PF
    WHERE PF.Id_Factura = FT.Id_Factura AND PF.Impuesto!=0),0))) AS Por_Pagar,

    IFNULL(FC.Pagado, 0) AS Pagado

    FROM Factura FT
    LEFT JOIN (SELECT Id_Factura, Factura, SUM(Valor) AS Pagado FROM Factura_Comprobante FC INNER JOIN Comprobante C ON FC.Id_Comprobante = C.Id_Comprobante WHERE C.Tipo = 'Ingreso' GROUP BY FC.Id_Factura) FC ON FT.Id_Factura = FC.Id_Factura AND FT.Codigo = FC.Factura
    WHERE FT.Estado_Factura = 'Sin Cancelar' AND FT.Codigo IN ($facturas)
)";



$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado = $oCon->getData();

unset($oCon);
$i=-1;
foreach ($resultado as $value) {$i++;
    $resultado[$i]['RetencionesFacturas']=[];
}

$query='SELECT * FROM Cliente WHERE Id_Cliente='.$cliente;
$oCon= new consulta();
$oCon->setQuery($query);
$cliente = $oCon->getData();
unset($oCon);

$datos['Facturas']=$resultado;
$datos['Cliente']=$cliente;

echo json_encode($datos);


?>