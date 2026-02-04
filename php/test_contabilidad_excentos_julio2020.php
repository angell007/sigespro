<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');
require_once('../class/html2pdf.class.php');
include_once('../class/NumeroALetra.php');


include_once('../class/class.querybasedatos.php');
include_once('../class/class.http_response.php');
require_once('../class/class.configuracion.php');

ini_set("memory_limit","32000M");
ini_set('max_execution_time', 0);



$query = 'SELECT F.Codigo, F.Fecha_Documento AS Fecha, P.Nombre_Comercial, P.Laboratorio_Comercial, PF.Cantidad, PF.Precio, PF.Descuento, PF.Impuesto, PF.Subtotal, 
(SELECT COUNT(PF2.Id_Producto_Factura) FROM Producto_Factura PF2 WHERE PF2.Id_Factura=PF.Id_Factura) AS Conteo
FROM Producto_Factura PF
INNER JOIN Factura F ON F.Id_Factura = PF.Id_Factura
INNER JOIN Producto P ON P.Id_Producto = PF.Id_Producto
WHERE P.Laboratorio_Comercial LIKE "%Exentos%"
AND PF.Impuesto = 0
AND DATE(F.Fecha_Documento) BETWEEN "2020-03-01" AND "2020-07-15"
HAVING Conteo = 1

UNION ALL

SELECT F.Codigo, F.Fecha_Documento AS Fecha, P.Nombre_Comercial, P.Laboratorio_Comercial, PF.Cantidad, PF.Precio_Venta, PF.Descuento, PF.Impuesto, PF.Subtotal,
(SELECT COUNT(PF2.Id_Producto_Factura_Venta) FROM Producto_Factura_Venta PF2 WHERE PF2.Id_Factura_Venta=PF.Id_Factura_Venta) AS Conteo
FROM Producto_Factura_Venta PF
INNER JOIN Factura_Venta F ON F.Id_Factura_Venta = PF.Id_Factura_Venta
INNER JOIN Producto P ON P.Id_Producto = PF.Id_Producto
WHERE P.Laboratorio_Comercial LIKE "%Exentos%"
AND PF.Impuesto = 0
AND DATE(F.Fecha_Documento) BETWEEN "2020-03-01" AND "2020-07-15"
HAVING Conteo = 1

ORDER BY Fecha';
    
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo("Multiple");
$productos = $oCon->getData();
unset($oCon);


$i=0;
foreach($productos as $prod){ $i++;
    
    if($prod["Conteo"]==1){
        $query = 'SELECT MC.* FROM Movimiento_Contable MC WHERE MC.Numero_Comprobante LIKE "'.$prod["Codigo"].'" AND MC.Id_Plan_Cuenta IN (429,119,828,433,836,127,441,844,135,437,840,131)
        ';
            
        $oCon= new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo("Multiple");
        $cuentas = $oCon->getData();
        unset($oCon);
        
       
        
        echo $i." - ".$prod["Codigo"]."<br>";
        
         foreach($cuentas as $cuenta){
            echo $cuenta["Id_Plan_Cuenta"]."<br>";
        }
        
        //var_dump($cuentas);
        echo "<br><br>";
        
    }
    
}



?>