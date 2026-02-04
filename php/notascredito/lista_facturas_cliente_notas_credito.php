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
FV.Id_Factura_Venta as IdFV, FV.codigo as Codigo , sum(Subtotal) as Valor_Factura 
FROM 
Factura_Venta FV
INNER JOIN Cliente C 
ON FV.Id_Cliente=C.Id_Cliente
INNER JOIN Producto_Factura_Venta PFV 
ON PFV.Id_Factura_Venta=FV.Id_Factura_Venta
WHERE 
FV.Id_Cliente='.$id.'
GROUP BY IdFV';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);
 
$resultado["Productos"]=$resultado;
echo json_encode($resultado);
?>