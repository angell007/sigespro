<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$condicion = '';

if (isset($_REQUEST['cod']) && $_REQUEST['cod'] != "") {
    $condicion .= " AND ARC.Codigo LIKE '%$_REQUEST[cod]%'";
}

if (isset($_REQUEST['compra']) && $_REQUEST['compra'] != "") {
    $condicion .= " AND (OCN.Codigo LIKE '%$_REQUEST[compra]%')";
}

if (isset($_REQUEST['proveedor']) && $_REQUEST['proveedor'] != "") {
    $condicion .= " AND P.Nombre LIKE '%$_REQUEST[proveedor]%'";
}

if (isset($_REQUEST['fecha']) && $_REQUEST['fecha'] != "") {
    $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha'])[0]);
    $fecha_fin = trim(explode(' - ', $_REQUEST['fecha'])[1]);
    $condicion .= " AND DATE_FORMAT(ARC.Fecha_Creacion, '%Y-%m-%d') BETWEEN '$fecha_inicio' AND '$fecha_fin'";
}

if (isset($_REQUEST['fecha2']) && $_REQUEST['fecha2'] != "") {
    $fecha_inicio = trim(explode(' - ', $_REQUEST['fecha2'])[0]);
    $fecha_fin = trim(explode(' - ', $_REQUEST['fecha2'])[1]);
    $condicion .= " AND ((OCN.Fecha BETWEEN '$fecha_inicio' AND '$fecha_fin'))";
}

if (isset($_REQUEST['fact']) && $_REQUEST['fact'] != "") {
    $condicion .= " HAVING Facturas LIKE '%$_REQUEST[fact]%'";
}

$query = 'SELECT COUNT(*) AS Total, ( SELECT GROUP_CONCAT(Factura) FROM Factura_Acta_Recepcion WHERE Id_Acta_Recepcion = ARC.Id_Acta_Recepcion ) as Facturas
        FROM Acta_Recepcion ARC
        LEFT JOIN Funcionario F
ON F.Identificacion_Funcionario = ARC.Identificacion_Funcionario
LEFT JOIN Orden_Compra_Nacional OCN
ON OCN.Id_Orden_Compra_Nacional = ARC.Id_Orden_Compra_Nacional
LEFT JOIN Bodega_Nuevo B
ON B.Id_Bodega_Nuevo = ARC.Id_Bodega_Nuevo 
INNER JOIN Proveedor P
ON P.Id_Proveedor = ARC.Id_Proveedor
WHERE ARC.Estado = "Aprobada" AND ARC.Tipo_Acta = "Punto_Dispensacion" '.$condicion;

$oCon= new consulta();
$oCon->setQuery($query);
$total = $oCon->getData();
unset($oCon);

####### PAGINACIÓN ######## 
$tamPag = 20; 
$numReg = $total["Total"]; 
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
        
$query = 'SELECT ARC.Id_Acta_Recepcion, ARC.Codigo, ARC.Fecha_Creacion, F.Imagen, B.Nombre as Bodega, OCN.Codigo as Codigo_Compra_N, P.Nombre as Proveedor,
OCN.Fecha as Fecha_Compra_N, 
(
    CASE 
        WHEN ARC.Tipo = "Nacional" THEN ARC.Id_Orden_Compra_Nacional
        ELSE ARC.Id_Orden_Compra_Internacional
    END
) AS Id_Orden_Compra,
( SELECT GROUP_CONCAT(Factura) FROM Factura_Acta_Recepcion WHERE Id_Acta_Recepcion = ARC.Id_Acta_Recepcion ) as Facturas,
ARC.Tipo
FROM Acta_Recepcion ARC 
LEFT JOIN Funcionario F
ON F.Identificacion_Funcionario = ARC.Identificacion_Funcionario
LEFT JOIN Orden_Compra_Nacional OCN
ON OCN.Id_Orden_Compra_Nacional = ARC.Id_Orden_Compra_Nacional
INNER JOIN Bodega_Nuevo B
ON B.Id_Bodega_Nuevo = ARC.Id_Bodega_Nuevo 
INNER JOIN Proveedor P
ON P.Id_Proveedor = ARC.Id_Proveedor
WHERE ARC.Estado = "Aprobada" AND ARC.Tipo_Acta = "Punto_Dispensacion"
'.$condicion.' ORDER BY Fecha_Creacion DESC, Codigo DESC LIMIT '.$limit.','.$tamPag;

//echo $query;

//echo $query;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$actarecepcion['actarecepciones'] = $oCon->getData();
unset($oCon);
          
$actarecepcion['numReg'] = $numReg;

echo json_encode($actarecepcion);


?>