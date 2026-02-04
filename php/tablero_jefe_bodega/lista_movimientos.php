<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');


 $query = 'SELECT MV.*, B.Nombre as Origen,
(SELECT COUNT(*) FROM Producto_Movimiento_Vencimiento PR WHERE PR.Id_Movimiento_Vencimiento = MV.Id_Movimiento_Vencimiento) as Items
FROM Movimiento_Vencimiento MV
INNER JOIN Bodega B
ON MV.Id_Bodega_Origen = B.Id_Bodega
WHERE MV.Estado="Pendiente"
HAVING Items>0
ORDER BY MV.Fecha DESC ' ;
       

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);

echo json_encode($resultado);
          
?>