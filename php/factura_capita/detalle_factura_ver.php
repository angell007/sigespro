<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/NumeroALetra.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = 'SELECT FV.Id_Factura_Capita, FV.Cufe, FV.Id_Resolucion,
            FV.Codigo_Qr, FV.Fecha_Documento as Fecha , FV.Observacion as observacion, FV.Codigo as Codigo,
            C.Id_Cliente as IdCliente ,C.Nombre as NombreCliente, C.Direccion as DireccionCliente, C.Ciudad as CiudadCliente, C.Credito as CreditoCliente, C.Cupo as CupoCliente, FV.Cuota_Moderadora, CONCAT(FV.Mes,"-","01") AS Mes, (SELECT Nombre FROM Departamento WHERE Id_Departamento = FV.Id_Departamento) AS Departamento, FV.Id_Departamento, IF(FV.Id_Regimen=1,"Contributivo","Subsidiado") AS Regimen
          FROM Factura_Capita FV
          INNER JOIN Cliente C
           ON FV.Id_Cliente = C.Id_Cliente
           AND FV.Id_Factura_Capita = '.$id ;



$oCon= new consulta();
$oCon->setQuery($query);
$dis = $oCon->getData();
unset($oCon);

$query2 = 'SELECT 
            DFC.*
           FROM Descripcion_Factura_Capita DFC
           INNER JOIN Factura_Capita FC
           ON DFC.Id_Factura_Capita = FC.Id_Factura_Capita
           WHERE DFC.Id_Factura_Capita =  '.$id ;

$oCon= new consulta();
$oCon->setQuery($query2);
$oCon->setTipo('Multiple');
$productos = $oCon->getData();
unset($oCon);

$subtotal = 0;
$descuentos = 0;
$iva = 0;

foreach ($productos as $prod) {
  $subtotal += $prod['Total'];
}

$total = $subtotal - $dis['Cuota_Moderadora'];


/* // consulta para total de facturas

$query5 = 'SELECT SUM(Subtotal) - ((SUM(Descuento)/100) * SUM(Subtotal)) + ((SUM(Impuesto)) * SUM(Subtotal)) as TotalFac FROM Producto_Factura WHERE Id_Factura = '.$id ;
$oCon= new consulta();
$oCon->setQuery($query5);
$totalFactura = $oCon->getData();
unset($oCon); */

$letras = NumeroALetras::convertir($total);

$oItem = new complex("Resolucion", "Id_Resolucion", $dis['Id_Resolucion']);
$resolucion = $oItem->getData();
unset($oItem);

$resolucion = array_map('utf8_encode', $resolucion);

$resultado["Datos"]=$dis;
$resultado["Productos"]=$productos;
$resultado["TotalFc"]=$total;
$resultado["letra"]=$letras;
$resultado["resolucion"]=$resolucion;

echo json_encode($resultado);


?>