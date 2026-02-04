<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condicion = '';

if (isset($_REQUEST['prov']) && $_REQUEST['prov']) {
    $condicion .= " AND P.Nombre LIKE '%$_REQUEST[prov]%'";
}

### LAS FACTURAS QUE SE REALIZARON EN SIGESPRO, SOLO DEBEN APARECER LAS QUE SE HICIERON DESDE EL AÑO 2019, LAS DEL AÑO 2018 SE CONTABILIZARON EN MANTIS.

$query = '
SELECT
r.Id_Proveedor,
r.Nombre,
MAX(r.Dias_Mora) AS Dias_Mora,
SUM(r.TOTAL) AS TOTAL
FROM
(
    (SELECT AR.Id_Proveedor, 
P.Nombre, 
IFNULL(MAX(IF(P.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(FAR.Fecha_Factura)) > P.Condicion_Pago, DATEDIFF(CURDATE(), DATE(FAR.Fecha_Factura)) - P.Condicion_Pago, 0), 0)),0) AS Dias_Mora, 
SUM( IF(PAR.Impuesto!=0, (PAR.Subtotal*(1+PAR.Impuesto/100)), PAR.Subtotal)) AS TOTAL 
FROM Factura_Acta_Recepcion FAR 
INNER JOIN Producto_Acta_Recepcion PAR 
ON FAR.Id_Acta_Recepcion = PAR.Id_Acta_Recepcion AND FAR.Factura = PAR.Factura 
INNER JOIN Acta_Recepcion AR 
ON AR.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion 
INNER JOIN Proveedor P 
ON AR.Id_Proveedor = P.Id_Proveedor
WHERE FAR.Estado = "Pendiente" AND YEAR(AR.Fecha_Creacion) = 2019
'.$condicion.' 
GROUP BY AR.Id_Proveedor)
/*UNION ALL(

    SELECT
    FP.Nit_Proveedor AS Id_Proveedor, 
    P.Nombre,
    IFNULL(MAX(IF(P.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(FP.Fecha_Factura)) > P.Condicion_Pago, DATEDIFF(CURDATE(), DATE(FP.Fecha_Factura)) - P.Condicion_Pago, 0), 0)),0) AS Dias_Mora, 
    SUM(FP.Saldo) AS TOTAL
    FROM
    Facturas_Proveedor_Mantis FP
    INNER JOIN Proveedor P 
    ON FP.Nit_Proveedor = P.Id_Proveedor
    WHERE FP.Estado = "Pendiente"
    '.$condicion.' 
    GROUP BY FP.Nit_Proveedor
)*/
) r
GROUP BY Id_Proveedor HAVING TOTAL > 0 ORDER BY TOTAL DESC
';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$total = $oCon->getData();
unset($oCon);

####### PAGINACIÓN ######## 
$tamPag = 30; 
$numReg = count($total); 
$paginas = ceil($numReg/$tamPag); 
$limit = ""; 
$paginaAct = "";

if (!isset($_REQUEST['pag']) || $_REQUEST['pag'] == '') { 
    $paginaAct = 1; 
    $limit = 0; 
} else { 
    $paginaAct = $_REQUEST['pag']; 
    $limit = ($paginaAct-1) * $tamPag; 
}


$query = 'SELECT
r.Id_Proveedor,
r.Nombre,
MAX(r.Dias_Mora) AS Dias_Mora,
SUM(r.TOTAL) AS TOTAL
FROM
(
    (SELECT AR.Id_Proveedor, 
P.Nombre, 
IFNULL(MAX(IF(P.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(FAR.Fecha_Factura)) > P.Condicion_Pago, DATEDIFF(CURDATE(), DATE(FAR.Fecha_Factura)) - P.Condicion_Pago, 0), 0)),0) AS Dias_Mora, 
SUM( IF(PAR.Impuesto!=0, (PAR.Subtotal*(1+PAR.Impuesto/100)), PAR.Subtotal)) AS TOTAL 
FROM Factura_Acta_Recepcion FAR 
INNER JOIN Producto_Acta_Recepcion PAR 
ON FAR.Id_Acta_Recepcion = PAR.Id_Acta_Recepcion AND FAR.Factura = PAR.Factura 
INNER JOIN Acta_Recepcion AR 
ON AR.Id_Acta_Recepcion = FAR.Id_Acta_Recepcion 
INNER JOIN Proveedor P 
ON AR.Id_Proveedor = P.Id_Proveedor
WHERE FAR.Estado = "Pendiente" AND YEAR(AR.Fecha_Creacion) = 2019
'.$condicion.' 
GROUP BY AR.Id_Proveedor)
/*UNION ALL(

    SELECT
    FP.Nit_Proveedor AS Id_Proveedor, 
    P.Nombre,
    IFNULL(MAX(IF(P.Condicion_Pago > 1, IF(DATEDIFF(CURDATE(), DATE(FP.Fecha_Factura)) > P.Condicion_Pago, DATEDIFF(CURDATE(), DATE(FP.Fecha_Factura)) - P.Condicion_Pago, 0), 0)),0) AS Dias_Mora, 
    SUM(FP.Saldo) AS TOTAL
    FROM
    Facturas_Proveedor_Mantis FP
    INNER JOIN Proveedor P 
    ON FP.Nit_Proveedor = P.Id_Proveedor
    WHERE FP.Estado = "Pendiente"
    '.$condicion.' 
    GROUP BY FP.Nit_Proveedor
)*/
) r
GROUP BY Id_Proveedor HAVING TOTAL > 0 ORDER BY TOTAL DESC LIMIT '.$limit.','.$tamPag;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado = $oCon->getData();
unset($oCon);


/* $query = 'SELECT SUM( IF(PAR.Impuesto!=0, (PAR.Subtotal*(1+(PAR.Impuesto/100))), PAR.Subtotal)) AS TOTAL FROM Producto_Acta_Recepcion PAR INNER JOIN Factura_Acta_Recepcion FAR ON FAR.Id_Acta_Recepcion = PAR.Id_Acta_Recepcion AND FAR.Factura = PAR.Factura INNER JOIN Acta_Recepcion AR ON AR.Id_Acta_Recepcion = PAR.Id_Acta_Recepcion INNER JOIN Proveedor P ON AR.Id_Proveedor = P.Id_Proveedor WHERE FAR.Estado = "Pendiente"';

$oCon= new consulta();
$oCon->setQuery($query);
$total = $oCon->getData();
unset($oCon); */

$total = 0;

foreach ($resultado as $value) {
    $total += $value['TOTAL'];
}

$query = 'SELECT
SUM(r.Pendientes) AS Pendientes
FROM
(
    (SELECT COUNT(*) AS Pendientes FROM Factura_Acta_Recepcion F INNER JOIN (SELECT Id_Acta_Recepcion FROM Acta_Recepcion WHERE YEAR(Fecha_Creacion) = 2019) AR ON F.Id_Acta_Recepcion = AR.Id_Acta_Recepcion WHERE F.Estado = "Pendiente") /*UNION (SELECT COUNT(*) AS Pendientes FROM Facturas_Proveedor_Mantis WHERE Estado = "Pendiente")*/
) r';

$oCon= new consulta();
$oCon->setQuery($query);
$facturas = $oCon->getData();
unset($oCon);


$datos['Lista']=$resultado;
$datos['numReg'] = $numReg;
$datos['Total']=number_format($total,2,".",",");
$datos['Pendientes']=$facturas['Pendientes'];

echo json_encode($datos);


?>