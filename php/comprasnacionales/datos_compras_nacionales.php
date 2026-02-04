<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = 'SELECT
ocn.Codigo,
ocn.Fecha AS Fecha_Compra,
ocn.Tipo,
ocn.Estado,
ocn.Aprobacion,
p.Nombre AS Proveedor,
IFNULL(b.Nombre, (SELECT PD.Nombre FROM Punto_Dispensacion PD WHERE PD.Id_Punto_Dispensacion=ocn.Id_Punto_Dispensacion)) AS Bodega,
DATE_FORMAT(ocn.Fecha_Entrega_Probable, "%d/%m/%Y") AS Fecha_Probable,
ocn.Observaciones,
ocn.Codigo_Qr
FROM Orden_Compra_Nacional ocn
LEFT JOIN Proveedor p ON ocn.Id_Proveedor=p.Id_Proveedor
LEFT JOIN Bodega_Nuevo b ON ocn.Id_Bodega_Nuevo=b.Id_Bodega_Nuevo
WHERE ocn.Id_Orden_Compra_Nacional='.$id ;

$oCon= new consulta();
$oCon->setQuery($query);
$compra = $oCon->getData();
unset($oCon);

$resultado = $compra;

echo json_encode($resultado);
          
?>