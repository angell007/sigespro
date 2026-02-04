<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$id = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : '' );

$query = "SELECT 
                D.Codigo as Codigo, Dep.Nombre, P.EPS, P.Nit, DC.Id_Cliente, Dep.Id_Departamento , CONCAT(P.Id_Paciente , ' - ', P.Primer_Nombre, ' ', P.Primer_Apellido,  ' - Regimen ' , R.Nombre ) as Paciente, D.Tipo AS Tipo_Dispensacion, P.Id_Regimen, D.Cuota
          FROM `Dispensacion` D 
          INNER JOIN Paciente P 
            ON P.Id_Paciente = D.Numero_Documento 
          INNER JOIN Departamento Dep 
            ON Dep.Id_Departamento = P.Id_Departamento 
          INNER JOIN Departamento_Cliente DC 
            ON P.Id_Departamento = DC.Id_Departamento
          INNER JOIN Regimen R
           ON P.Id_Regimen = R.Id_Regimen
          WHERE `Id_Dispensacion` = ".$id;

$oCon= new consulta();
//$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$nombre = $oCon->getData();
unset($oCon);


if ($nombre["Tipo_Dispensacion"] == "Evento" || ($nombre["Tipo_Dispensacion"] == "NoPos" && $nombre["Id_Regimen"] == 1)) {
  // busco cliente
  $query1 = 'SELECT Id_Cliente as IdClienteFactura, Nombre as ClienteFactura, Condicion_Pago as CondicionPago FROM Cliente WHERE Id_Cliente ='.$nombre["Nit"];

} else {
  // busco cliente
  $query1 = 'SELECT Id_Cliente as IdClienteFactura, Nombre as ClienteFactura, Condicion_Pago as CondicionPago FROM Cliente WHERE Id_Cliente ='.$nombre["Id_Cliente"];
  
}

$oCon= new consulta();
$oCon->setQuery($query1);
$factura = $oCon->getData();
unset($oCon);

// busco homologo
$query2 = 'SELECT Id_Cliente as IdClienteHomologo, Nombre as ClienteHomologo , Condicion_Pago as CondicionPagoHomologo FROM  Cliente WHERE Id_Cliente ='.$nombre["Nit"];
$oCon= new consulta();
$oCon->setQuery($query2);
$homologo = $oCon->getData();
unset($oCon);

$band_homologo = false;

if ($nombre["Tipo_Dispensacion"] == "Evento") {
  //busco los productos 
$query3 = 'SELECT  
CONCAT_WS(" ", p.Nombre_Comercial, p.Presentacion, p.Concentracion, " (",p.Principio_Activo ,") ", p.Cantidad, p.Unidad_Medida ) as Nombre ,
i.Costo as CostoUnitario,
i.Lote as Lote,
i.Id_Inventario as Id_Inventario,
i.Codigo_CUM as Cum,
p.Invima as Invima,
i.Fecha_Vencimiento as Fecha_Vencimiento,
p.Laboratorio_Generico as Laboratorio_Generico,
p.Laboratorio_Comercial as Laboratorio_Comercial,
p.Presentacion as Presentacion,
PD.Cantidad_Formulada as Cantidad,
PD.Id_Producto_Dispensacion,
p.Gravado as Gravado,
p.Id_Producto,
"0" as Descuento,
"0" as Impuesto,
"0" as Subtotal,
#IFNULL(PE.Precio, 0) as Precio_Venta_Factura,
(
CASE
  WHEN PRG.Codigo_Cum IS NOT NULL THEN PRG.Precio
  WHEN PE.Codigo_Cum IS NOT NULL THEN PE.Precio
  ELSE 0
END
) AS Precio_Venta_Factura,
0 as Precio,
0 as Iva,
0 as Total_Descuento,
IF(PE.Id_Producto_Evento IS NULL, 1, 0) AS Registrar
FROM Producto_Dispensacion as PD 
LEFT JOIN Precio_Regulado PRG
ON PD.Cum = PRG.Codigo_Cum
LEFT JOIN Producto_Evento PE
ON PD.Cum = PE.Codigo_Cum AND PE.Nit_EPS = '.$nombre['Nit'].'
INNER JOIN Producto p
on p.Id_Producto=PD.Id_Producto
INNER JOIN Inventario i
ON i.Id_Inventario = PD.Id_Inventario
WHERE PD.Id_Dispensacion =  '.$id ;

// echo $query3;
// exit;
} elseif (($nombre["Tipo_Dispensacion"] == "NoPos" && $nombre["Id_Regimen"] == 1)) {
  $query3 = 'SELECT  
CONCAT_WS(" ", p.Nombre_Comercial, p.Presentacion, p.Concentracion, " (",p.Principio_Activo ,") ", p.Cantidad, p.Unidad_Medida ) as Nombre ,
i.Costo as CostoUnitario,
i.Lote as Lote,
i.Id_Inventario as Id_Inventario,
i.Codigo_CUM as Cum,
p.Invima as Invima,
i.Fecha_Vencimiento as Fecha_Vencimiento,
p.Laboratorio_Generico as Laboratorio_Generico,
p.Laboratorio_Comercial as Laboratorio_Comercial,
p.Presentacion as Presentacion,
PD.Cantidad_Formulada as Cantidad,
PD.Id_Producto_Dispensacion,
p.Gravado as Gravado,
p.Id_Producto,
"0" as Descuento,
"0" as Impuesto,
"0" as Subtotal,
#IFNULL(PNP.Precio,0) as Precio_Venta_Factura,
(
CASE
  WHEN PRG.Codigo_Cum IS NOT NULL THEN PRG.Precio
  WHEN PNP.Cum IS NOT NULL THEN PNP.Precio
  ELSE 0
END
) AS Precio_Venta_Factura,
0 as Precio,
0 as Iva,
0 as Total_Descuento,
PNP.Id_Producto_NoPos,
IF(PNP.Id_Producto_NoPos IS NULL, 1, 0) AS Registrar
FROM Producto_Dispensacion as PD 
INNER JOIN Producto p
on p.Id_Producto=PD.Id_Producto
INNER JOIN Inventario i
ON i.Id_Inventario = PD.Id_Inventario
LEFT JOIN Precio_Regulado PRG
ON PD.Cum = PRG.Codigo_Cum
LEFT JOIN (SELECT PNP.* FROM Producto_NoPos PNP INNER JOIN Departamento_Lista_Nopos DLN ON DLN.Id_Lista_Producto_Nopos = PNP.Id_Lista_Producto_Nopos WHERE DLN.Id_Departamento = '.$nombre["Id_Departamento"].') PNP
ON PD.Cum = PNP.Cum
WHERE PD.Id_Dispensacion =  '.$id ;
} else {
  $band_homologo = true;
//busco los productos 
$query3 = 'SELECT  
CONCAT_WS(" ", p.Nombre_Comercial, p.Presentacion, p.Concentracion, " (",p.Principio_Activo ,") ", p.Cantidad, p.Unidad_Medida ) as Nombre ,
i.Costo as CostoUnitario,
i.Lote as Lote,
i.Id_Inventario as Id_Inventario,
i.Codigo_CUM as Cum,
p.Invima as Invima,
i.Fecha_Vencimiento as Fecha_Vencimiento,
p.Laboratorio_Generico as Laboratorio_Generico,
p.Laboratorio_Comercial as Laboratorio_Comercial,
p.Presentacion as Presentacion,
PD.Cantidad_Formulada as Cantidad,
PD.Id_Producto_Dispensacion,
p.Gravado as Gravado,
p.Id_Producto,
"0" as Descuento,
"0" as Impuesto,
"0" as Subtotal,
#IFNULL(PNP.Precio,0) as Precio_Venta_Factura,
(
CASE
  WHEN PRG.Codigo_Cum IS NOT NULL THEN PRG.Precio
  WHEN PNP.Cum IS NOT NULL THEN PNP.Precio
  ELSE 0
END
) AS Precio_Venta_Factura,
0 as Precio,
0 as Iva,
0 as Total_Descuento,
PNP.Cum_Homologo,
#PNP.Precio_Homologo,
IF(PRG.Codigo_Cum IS NOT NULL,PRG.Precio,PNP.Precio_Homologo) AS Precio_Homologo,
PNP.Detalle_Homologo,
PNP.Id_Producto_NoPos,
IF(PNP.Id_Producto_NoPos IS NULL, 1, 0) AS Registrar
FROM Producto_Dispensacion as PD 
INNER JOIN Producto p
on p.Id_Producto=PD.Id_Producto
INNER JOIN Inventario i
ON i.Id_Inventario = PD.Id_Inventario
LEFT JOIN Precio_Regulado PRG
ON PD.Cum = PRG.Codigo_Cum
LEFT JOIN (SELECT PNP.* FROM Producto_NoPos PNP INNER JOIN Departamento_Lista_Nopos DLN ON DLN.Id_Lista_Producto_Nopos = PNP.Id_Lista_Producto_Nopos WHERE DLN.Id_Departamento = '.$nombre["Id_Departamento"].') PNP
ON PD.Cum = PNP.Cum
WHERE PD.Id_Dispensacion =  '.$id ;
}


$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query3);
$productos = $oCon->getData();
$productosHom = $productos;
unset($oCon);

$i=-1;
foreach($productos as $lista){$i++;
$productos[$i]['producto'] = $productos[$i];
$productosHom[$i]['producto'] = $productos[$i];

/* if ($lista['Cum_Homologo'] != "") {
  $q = 'SELECT CONCAT_WS(" ", p.Nombre_Comercial, p.Presentacion, p.Concentracion, " (",p.Principio_Activo ,") ", p.Cantidad, p.Unidad_Medida ) as Nombre ,
i.Costo as CostoUnitario,
i.Lote as Lote,
i.Id_Inventario as Id_Inventario,
i.Codigo_CUM as Cum,
p.Invima as Invima,
i.Fecha_Vencimiento as Fecha_Vencimiento,
p.Laboratorio_Generico as Laboratorio_Generico,
p.Laboratorio_Comercial as Laboratorio_Comercial,
p.Presentacion as Presentacion,
'.$lista['Cantidad_Formulada'].' as Cantidad,
'.$lista['Id_Producto_Dispensacion'].' AS Id_Producto_Dispensacion,
p.Gravado as Gravado,
"0" as Descuento,
"0" as Impuesto,
"0" as Subtotal,
"0" as Precio_Venta_Factura,
"0" as Precio,
0 as Iva,
0 as Total_Descuento,
PNP.Cum_Homologo FROM Producto_NoPos PNP INNER JOIN Producto p ON PNP.Cum_Homologo = p.Codigo_CUM INNER JOIN Inventario i ON P.Id_Producto = I.Id_Inventario WHERE PNP.Cum_Homologo = ' . $lista['Cum_Homologo'] . ' AND PNP.Id_Lista_Producto_Nopos = ' . $lista['Id_Lista_Producto_Nopos'];

$oCon= new consulta();
$oCon->setQuery($q);
$productosHom[$i] = $oCon->getData();
$productosHom[$i]['producto'] = $oCon->getData();
unset($oCon);
} else {
  $productosHom[$i]['producto'] = $productos[$i];
} */



}


 // busco si el cliente tiene contrato
$query5 = 'SELECT Id_Contrato ,count(*) as conteo FROM `Contrato` where Id_Cliente ='.$homologo['IdClienteHomologo'] ;
$oCon= new consulta();
$oCon->setQuery($query5);
$contratoCliente= $oCon->getData();
unset($oCon);

$preciosTabla=[];
$posiciones=[];
/* if($contratoCliente['conteo'] > 0){
    // debo consultar productos_contrato
    $i=-1;
    foreach($productos as $cum){$i++;
        $query6 = 'SELECT `Precio` , Homologo  FROM `Producto_Contrato` WHERE Id_Contrato = "'.$contratoCliente['Id_Contrato'].'" AND `Cum` ="'.$cum['Cum'].'"' ;
        $oCon= new consulta();
        $oCon->setQuery($query6);
        $PrecioCum= $oCon->getData();
        unset($oCon);
                    
         if(isset($PrecioCum['Precio'])){
               $productos[$i]['Precio_Venta_Factura'] = ($PrecioCum['Precio']  - $PrecioCum['Homologo']);
               $productosHom[$i]['Precio'] = $PrecioCum['Homologo'];
               $productos[$i]['Precio'] = $PrecioCum['Precio'];
         }
    }
}
else{
 // debo traerme la lista de producto lista ganancia = 1;
 $i=-1;
     foreach($productos as $cum){$i++;
          $query6 = 'SELECT `Precio`, Cum FROM `Producto_Lista_Ganancia` WHERE `Id_Lista_Ganancia`= 1 AND `Cum` ="'.$cum['Cum'].'"' ;
          $oCon= new consulta();
          $oCon->setQuery($query6);
          $lista= $oCon->getData();
          unset($oCon);
          
          $productos[$i]['Precio'] = ($cum['Precio']  - $lista['Precio']);   
    }
}
 */
$resultado['productos'] = $productos;
$resultado['productoHomologo'] =$productosHom;
$resultado['factura'] = $factura;
$resultado['homologo'] = $homologo;
$resultado['encabezado'] = $nombre;


echo json_encode($resultado);