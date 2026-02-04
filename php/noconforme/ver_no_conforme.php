<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' ); 

$query='SELECT 
     ifnull(B.Nombre, BN.Nombre) AS Nombre_Bodega,
     AR.Id_Proveedor,
     NC.Id_No_Conforme, 
     P.Nombre as Proveedor, 
     NC.Id_Acta_Recepcion_Compra,  
     OCN.Codigo as Compra, 
     AR.Codigo AS Acta 
     FROM No_Conforme NC 
     LEFT JOIN Acta_Recepcion AR ON NC.Id_Acta_Recepcion_Compra=AR.Id_Acta_Recepcion 
     LEFT JOIN Proveedor P ON AR.Id_Proveedor=P.Id_Proveedor 
     LEFT JOIN Orden_Compra_Nacional OCN ON AR.Id_Orden_Compra_Nacional=OCN.Id_Orden_Compra_Nacional 
     LEFT JOIN Bodega B ON AR.Id_Bodega=B.Id_Bodega 
     LEFT JOIN Bodega_Nuevo BN ON AR.Id_Bodega_Nuevo=BN.Id_Bodega_Nuevo 
     WHERE NC.Id_No_Conforme= '.$id;
        
$oCon= new consulta();
//$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$informacion = $oCon->getData();
unset($oCon);

$query = "SELECT F.Factura, F.Fecha_Factura FROM Factura_Acta_Recepcion F WHERE F.Id_Acta_Recepcion=".$informacion['Id_Acta_Recepcion_Compra'];
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$facturas = $oCon->getData();
unset($oCon);

$query='SELECT PRD.Nombre_Comercial, PRD.Laboratorio_Comercial, PRD.Laboratorio_Generico, PNC.Cantidad, IFNULL(CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," ", PRD.Cantidad," ", PRD.Unidad_Medida),CONCAT(PRD.Nombre_Comercial," ",PRD.Laboratorio_Comercial)) as Nombre_Producto, PNC.Observaciones AS Motivo, PRD.Id_Producto
FROM Producto_No_Conforme PNC  
INNER JOIN Producto PRD  ON PNC.Id_Producto=PRD.Id_Producto
WHERE PNC.Id_No_Conforme='.$id;

$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$productos = $oCon->getData();
unset($oCon);


$resultado['encabezado'] = $informacion;
$resultado['Productos'] = $productos;
$resultado['Facturas'] = $facturas;

echo json_encode($resultado);
?>