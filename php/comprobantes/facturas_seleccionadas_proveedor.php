<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
$id = ( isset( $_REQUEST['prov'] ) ? $_REQUEST['prov'] : '' );
$facturas = ( isset( $_REQUEST['facturas'] ) ? $_REQUEST['facturas'] : '' );

$query = "SELECT
FAR.*, FAR.Factura AS Codigo,
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
IFNULL(FC.Pagado, 0) AS Pagado
FROM
Factura_Acta_Recepcion FAR
INNER JOIN Acta_Recepcion AR
ON FAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion
INNER JOIN Producto_Acta_Recepcion PAR
ON PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion
LEFT JOIN (SELECT Id_Factura, Factura, SUM(Valor) AS Pagado FROM Factura_Comprobante FC INNER JOIN Comprobante C ON FC.Id_Comprobante = C.Id_Comprobante WHERE C.Tipo = 'Ingreso' GROUP BY FC.Id_Factura) FC ON FAR.Id_Factura_Acta_Recepcion = FC.Id_Factura AND FAR.Factura = FC.Factura
WHERE FAR.Estado = 'Pendiente' AND FAR.Id_Factura_Acta_Recepcion IN ($facturas)
GROUP BY FAR.Id_Acta_Recepcion, FAR.Factura ";

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