<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$inicio = ( isset( $_REQUEST['inicio'] ) ? $_REQUEST['inicio'] : '' );
$fin = ( isset( $_REQUEST['fin'] ) ? $_REQUEST['fin'] : '' );
$proveedor = ( isset( $_REQUEST['proveedor'] ) ? $_REQUEST['proveedor'] : false );

$condicion='';

if($proveedor){
    $condicion=' AND AR.Id_Proveedor='.$proveedor;
}

if(isset($_REQUEST['nro_factura']) && $_REQUEST['nro_factura'] != ''){
	$condicion .= ' AND FAR.Factura LIKE "%'.$_REQUEST["nro_factura"].'%"';
}


$query = "SELECT COUNT(*) AS Total FROM (
SELECT AR.Id_Acta_Recepcion, AR.Id_Proveedor, P.Nombre , FAR.Factura, FAR.Fecha_Factura, 
(SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
FROM Producto_Acta_Recepcion PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND P.Gravado='Si') as Gravada,
(SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
FROM Producto_Acta_Recepcion PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND P.Gravado='No') as Excenta,
((SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
FROM Producto_Acta_Recepcion PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND P.Gravado='Si')+(SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
FROM Producto_Acta_Recepcion PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND P.Gravado='No')) as Total_Compra,
((SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
FROM Producto_Acta_Recepcion PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND P.Gravado='Si')+(SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
FROM Producto_Acta_Recepcion PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND P.Gravado='No')+(SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio)*(19/100)),0)
FROM Producto_Acta_Recepcion PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND P.Gravado='Si')) AS Neto_Factura,
(SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio)*(19/100)),0)
FROM Producto_Acta_Recepcion PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND P.Gravado='Si') as Iva, AR.Codigo AS Codigo_Acta

FROM Acta_Recepcion AR 
INNER JOIN Factura_Acta_Recepcion FAR 
ON FAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion
INNER JOIN Proveedor P 
ON P.Id_Proveedor = AR.Id_Proveedor
WHERE DATE_FORMAT(AR.Fecha_Creacion, '%Y-%m-%d') BETWEEN '".$inicio."' AND '".$fin."'".$condicion."
UNION ALL (
    SELECT AR.Id_Acta_Recepcion_Internacional, AR.Id_Proveedor, P.Nombre , FAR.Factura, FAR.Fecha_Factura, 
(SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
FROM Producto_Acta_Recepcion_Internacional PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion_Internacional = AR.Id_Acta_Recepcion_Internacional AND P.Gravado='Si') as Gravada,
(SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
FROM Producto_Acta_Recepcion_Internacional PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion_Internacional = AR.Id_Acta_Recepcion_Internacional AND P.Gravado='No') as Excenta,
((SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
FROM Producto_Acta_Recepcion_Internacional PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion_Internacional = AR.Id_Acta_Recepcion_Internacional AND P.Gravado='Si')+(SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
FROM Producto_Acta_Recepcion_Internacional PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion_Internacional = AR.Id_Acta_Recepcion_Internacional AND P.Gravado='No')) as Total_Compra,
((SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
FROM Producto_Acta_Recepcion_Internacional PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion_Internacional = AR.Id_Acta_Recepcion_Internacional AND P.Gravado='Si')+(SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
FROM Producto_Acta_Recepcion_Internacional PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion_Internacional = AR.Id_Acta_Recepcion_Internacional AND P.Gravado='No')+(SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio)*(19/100)),0)
FROM Producto_Acta_Recepcion_Internacional PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion_Internacional = AR.Id_Acta_Recepcion_Internacional AND P.Gravado='Si')) AS Neto_Factura,
(SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio)*(19/100)),0)
FROM Producto_Acta_Recepcion_Internacional PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion_Internacional = AR.Id_Acta_Recepcion_Internacional AND P.Gravado='Si') as Iva, AR.Codigo AS Codigo_Acta

FROM Acta_Recepcion_Internacional AR 
INNER JOIN Factura_Acta_Recepcion_Internacional FAR 
ON FAR.Id_Acta_Recepcion_Internacional = AR.Id_Acta_Recepcion_Internacional
INNER JOIN Proveedor P 
ON P.Id_Proveedor = AR.Id_Proveedor
WHERE DATE_FORMAT(AR.Fecha_Creacion, '%Y-%m-%d') BETWEEN '".$inicio."' AND '".$fin."'".$condicion."
)
) AS r";

$oCon= new consulta();
$oCon->setQuery($query);
$total = $oCon->getData();
unset($oCon);

####### PAGINACIÓN ######## 
$tamPag = 10; 
$numReg = $total['Total']; 
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

$resultado['numReg'] = $numReg;

$query = "SELECT AR.Id_Acta_Recepcion, AR.Id_Bodega_Nuevo, AR.Id_Proveedor, P.Nombre , FAR.Factura, FAR.Fecha_Factura, 
(SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
FROM Producto_Acta_Recepcion PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND P.Gravado='Si' AND PAR.Factura=FAR.Factura) as Gravada,
(SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
FROM Producto_Acta_Recepcion PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND P.Gravado='No' AND PAR.Factura=FAR.Factura) as Excenta,
((SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
FROM Producto_Acta_Recepcion PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND P.Gravado='Si' AND PAR.Factura=FAR.Factura)+(SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
FROM Producto_Acta_Recepcion PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND P.Gravado='No' AND PAR.Factura=FAR.Factura)) as Total_Compra,
((SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
FROM Producto_Acta_Recepcion PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND P.Gravado='Si' AND PAR.Factura=FAR.Factura)+(SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
FROM Producto_Acta_Recepcion PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND P.Gravado='No' AND PAR.Factura=FAR.Factura)+(SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio)*(19/100)),0)
FROM Producto_Acta_Recepcion PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND P.Gravado='Si' AND PAR.Factura=FAR.Factura)) AS Neto_Factura,
(SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio)*(19/100)),0)
FROM Producto_Acta_Recepcion PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion AND P.Gravado='Si' AND PAR.Factura=FAR.Factura) as Iva, AR.Codigo AS Codigo_Acta

FROM  Acta_Recepcion AR 
INNER JOIN Factura_Acta_Recepcion FAR 
ON FAR.Id_Acta_Recepcion = AR.Id_Acta_Recepcion
INNER JOIN Proveedor P 
ON P.Id_Proveedor = AR.Id_Proveedor
WHERE DATE_FORMAT(AR.Fecha_Creacion, '%Y-%m-%d') BETWEEN '".$inicio."' AND '".$fin."'".$condicion."
UNION ALL (
    SELECT AR.Id_Acta_Recepcion_Internacional, AR.Id_Bodega_Nuevo, AR.Id_Proveedor, P.Nombre , FAR.Factura, FAR.Fecha_Factura, 
(SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
FROM Producto_Acta_Recepcion_Internacional PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion_Internacional = AR.Id_Acta_Recepcion_Internacional AND P.Gravado='Si') as Gravada,
(SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
FROM Producto_Acta_Recepcion_Internacional PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion_Internacional = AR.Id_Acta_Recepcion_Internacional AND P.Gravado='No') as Excenta,
((SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
FROM Producto_Acta_Recepcion_Internacional PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion_Internacional = AR.Id_Acta_Recepcion_Internacional AND P.Gravado='Si')+(SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
FROM Producto_Acta_Recepcion_Internacional PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion_Internacional = AR.Id_Acta_Recepcion_Internacional AND P.Gravado='No')) as Total_Compra,
((SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
FROM Producto_Acta_Recepcion_Internacional PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion_Internacional = AR.Id_Acta_Recepcion_Internacional AND P.Gravado='Si')+(SELECT IFNULL(SUM(PAR.Cantidad*PAR.Precio),0)
FROM Producto_Acta_Recepcion_Internacional PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion_Internacional = AR.Id_Acta_Recepcion_Internacional AND P.Gravado='No')+(SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio)*(19/100)),0)
FROM Producto_Acta_Recepcion_Internacional PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion_Internacional = AR.Id_Acta_Recepcion_Internacional AND P.Gravado='Si')) AS Neto_Factura,
(SELECT IFNULL(SUM((PAR.Cantidad*PAR.Precio)*(19/100)),0)
FROM Producto_Acta_Recepcion_Internacional PAR
INNER JOIN Producto P
ON PAR.Id_Producto = P.Id_Producto
WHERE PAR.Id_Acta_Recepcion_Internacional = AR.Id_Acta_Recepcion_Internacional AND P.Gravado='Si') as Iva, AR.Codigo AS Codigo_Acta

FROM Acta_Recepcion_Internacional AR 
INNER JOIN Factura_Acta_Recepcion_Internacional FAR 
ON FAR.Id_Acta_Recepcion_Internacional = AR.Id_Acta_Recepcion_Internacional
INNER JOIN Proveedor P 
ON P.Id_Proveedor = AR.Id_Proveedor
WHERE DATE_FORMAT(AR.Fecha_Creacion, '%Y-%m-%d') BETWEEN '".$inicio."' AND '".$fin."'".$condicion."
) 
LIMIT ".$limit.",".$tamPag;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado['records'] = $oCon->getData();
unset($oCon);

echo json_encode($resultado);

?>