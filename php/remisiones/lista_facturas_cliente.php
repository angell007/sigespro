<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$nombre = ( isset( $_REQUEST['nombre'] ) ? $_REQUEST['nombre'] : '' );

$query = 'SELECT 
                FV.Id_Factura_Venta as IdFV, FV.codigo as Codigo , sum(Subtotal) as Valor_Factura 
          FROM 
                Factura_Venta FV , Cliente C , Producto_Factura_Venta PFV 
          WHERE 
                C.Id_Cliente = FV.Id_cliente 
          AND 
                PFV.Id_Factura_Venta = FV.Id_Factura_Venta 
          AND 
                C.Nombre= "'.$nombre.'" 
          GROUP BY IdFV'  ;


$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$resultado = $oCon->getData();
unset($oCon);

$resultado["Productos"]=$resultado;
echo json_encode($resultado);
?>