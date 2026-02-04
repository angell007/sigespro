<?php
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
    header('Content-Type: application/json');

    require_once('../../config/start.inc.php');
    include_once('../../class/class.lista.php');
    include_once('../../class/class.complex.php');
    include_once('../../class/class.consulta.php');

$condicion = '';

if (isset($_REQUEST['nom']) && $_REQUEST['nom'] != "") {
    if($condicion==''){
        $condicion .= " and P.Nombre_Comercial LIKE '%$_REQUEST[nom]%'";
    }
}
if (isset($_REQUEST['lot']) && $_REQUEST['lot'] != "") {
    if($condicion==''){
        $condicion .= "and INN.Lote LIKE '%$_REQUEST[lot]%'";
    }
}
if (isset($_REQUEST['pro']) && $_REQUEST['pro'] != "") {
    if($condicion==''){
        $condicion .= "and PR.Nombre LIKE '%$_REQUEST[pro]%'";
    }
}

$query = "SELECT SUM(total) AS Total
            FROM 
            (SELECT COUNT(distinct P.Nombre_Comercial) AS Total
            FROM Proveedor PR 
            INNER JOIN Orden_Compra_Nacional OCN ON PR.Id_Proveedor = OCN.Id_Proveedor
            INNER JOIN Acta_Recepcion AR ON OCN.Id_Orden_Compra_Nacional = AR.Id_Orden_Compra_Nacional AND OCN.Id_Bodega = AR.Id_Bodega
            INNER JOIN Producto_Acta_Recepcion PAC ON PAC.Id_Acta_Recepcion = AR.Id_Acta_Recepcion
            INNER JOIN Inventario_Nuevo INN ON PAC.Lote = INN.Lote AND PAC.Id_Producto = INN.Id_Producto
            INNER JOIN Producto P ON INN.Id_Producto = P.Id_Producto
            WHERE INN.Cantidad > 0 
            AND (DATE_ADD(INN.Fecha_Vencimiento, interval -PR.Meses_Devolucion MONTH ) > CURDATE() 
            OR PR.Meses_Devolucion = 0) $condicion
            GROUP BY PAC.Lote) A";
$oCon= new consulta();
$oCon->setQuery($query);
$total = $oCon->getData();
unset($oCon);

####### PAGINACIÓN ########
$tamPag = 10;
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

$query = "SELECT distinct P.Nombre_Comercial NomProducto, INN.Lote, SUM(INN.Cantidad) Cantidades, 
            DATE_ADD(INN.Fecha_Vencimiento, interval -PR.Meses_Devolucion MONTH ) AS FechaEntrega,
            INN.Fecha_Vencimiento, PR.Meses_Devolucion, CURDATE() AS Fecha, PR.Nombre as NomProveedor, 
            INN.Id_Producto, PR.Id_Proveedor
            FROM Proveedor PR 
            INNER JOIN Orden_Compra_Nacional OCN ON PR.Id_Proveedor = OCN.Id_Proveedor
            INNER JOIN Acta_Recepcion AR ON OCN.Id_Orden_Compra_Nacional = AR.Id_Orden_Compra_Nacional AND OCN.Id_Bodega = AR.Id_Bodega
            INNER JOIN Producto_Acta_Recepcion PAC ON PAC.Id_Acta_Recepcion = AR.Id_Acta_Recepcion
            INNER JOIN Inventario_Nuevo INN ON PAC.Lote = INN.Lote AND PAC.Id_Producto = INN.Id_Producto
            INNER JOIN Producto P ON INN.Id_Producto = P.Id_Producto
            WHERE INN.Cantidad > 0 AND (DATE_ADD(INN.Fecha_Vencimiento, interval -PR.Meses_Devolucion MONTH ) > CURDATE() 
            OR PR.Meses_Devolucion = 0) $condicion
            GROUP BY P.Nombre_Comercial, INN.Lote, INN.Fecha_Vencimiento, PR.Meses_Devolucion, PR.Nombre, INN.Id_Producto, PR.Id_Proveedor
            ORDER BY FechaEntrega ASC  , INN.Fecha_Vencimiento ASC LIMIT ".$limit.",".$tamPag;
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$datos['Lista'] = $oCon->getData();
unset($oCon);

$datos['numReg'] = $numReg;

echo json_encode($datos);