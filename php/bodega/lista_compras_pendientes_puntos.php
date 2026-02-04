<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$tipoCompra = ( isset( $_REQUEST['compra'] ) ? $_REQUEST['compra'] : '' );
$punto = ( isset( $_REQUEST['punto'] ) ? $_REQUEST['punto'] : '' );

$condicion = '';

if (isset($_REQUEST['cod']) && $_REQUEST['cod'] != "") {
    $condicion .= " AND OC.Codigo LIKE '%$_REQUEST[cod]%'";
}
if (isset($_REQUEST['proveedor']) && $_REQUEST['proveedor'] != "") {
    $condicion .= " AND P.Nombre LIKE '%$_REQUEST[proveedor]%'";
}




switch($tipoCompra){
    
    case "Nacional":{
        $query = 'SELECT OC.Id_Orden_Compra_Nacional, OC.Fecha, F.Imagen, P.Nombre , OC.Codigo,
(SELECT COUNT(*) FROM Producto_Orden_Compra_Nacional PR WHERE PR.Id_Orden_Compra_Nacional = OC.Id_Orden_Compra_Nacional) as Items
FROM Orden_Compra_Nacional OC
LEFT JOIN Funcionario F
ON OC.Identificacion_Funcionario = F.Identificacion_Funcionario
LEFT JOIN Proveedor P
ON OC.Id_Proveedor = P.Id_Proveedor
WHERE OC.Estado <> "Recibida" AND OC.Estado <> "Anulada" AND OC.Aprobacion = "Aprobada" AND OC.Id_Bodega = 0 AND OC.Id_Punto_Dispensacion = '.$punto.' '.$condicion.'
ORDER BY OC.Fecha DESC, OC.Codigo DESC' ;
        break;
 }

}

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);

echo json_encode($resultado);
          
?>