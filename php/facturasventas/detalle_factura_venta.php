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

$query = 'SELECT 
FV.Fecha_Documento as Fecha, FV.Cufe, FV.Id_Resolucion, FV.Observacion_Factura_Venta as observacion, FV.Codigo as Codigo, FV.Codigo_Qr, IF(FV.Condicion_Pago=1,"CONTADO",FV.Condicion_Pago) as Condicion_Pago , FV.Fecha_Pago as Fecha_Pago ,
C.Id_Cliente as IdCliente ,C.Nombre as NombreCliente, C.Direccion as DireccionCliente, M.Nombre as CiudadCliente, C.Credito as CreditoCliente, C.Celular AS Telefono, FV.Id_Factura_Venta ,(SELECT R.Observaciones FROM Remision R WHERE Id_Factura = FV.Id_Factura_Venta Order By R.Id_Remision ASC LIMIT 1) as Observaciones2
FROM Factura_Venta FV
INNER JOIN Cliente C ON FV.Id_Cliente = C.Id_Cliente
INNER JOIN Municipio M ON C.Ciudad=M.Id_Municipio
AND FV.Id_Factura_Venta ='.$id ;

$oCon= new consulta();
$oCon->setQuery($query);
$dis = $oCon->getData();
unset($oCon);

$query2 = 'SELECT 
IFNULL(CONCAT(P.Nombre_Comercial, " - ",P.Principio_Activo, " ", P.Cantidad,"", P.Unidad_Medida, " " , P.Presentacion, "\n", P.Invima, " CUM:", P.Codigo_Cum), CONCAT(P.Nombre_Comercial, " LAB-", P.Laboratorio_Comercial)) as producto, 
P.Id_Producto,
IF(P.Laboratorio_Generico IS NULL,P.Laboratorio_Comercial,P.Laboratorio_Generico) as Laboratorio,
P.Presentacion,
P.Codigo_Cum as Cum, 

(SELECT Costo_Promedio FROM Costo_Promedio WHERE Id_Producto = P.Id_Producto) as CostoUnitario,
 IFNULL(I.Lote,INV.Lote) AS Lote,
 IFNULL(I.Id_Inventario,INV.Id_Inventario_Nuevo) as Id_Inventario,
 IFNULL(I.Fecha_Vencimiento,INV.Fecha_Vencimiento) as Fecha_Vencimiento,
 
PFV.Precio_Venta as Costo_unitario,
PFV.Cantidad as Cantidad,
PFV.Precio_Venta as PrecioVenta,
(PFV.Cantidad * PFV.Precio_Venta*(1-(PFV.Descuento/100)) ) as Subtotal,
PFV.Id_Producto_Factura_Venta as idPFV,
(CASE  
  WHEN P.Gravado = "Si" AND C.Impuesto="Si" THEN "19%" 
  ELSE "0%" 
END) as Impuesto,
CONCAT(PFV.Impuesto,"%") as Impuesto
FROM Producto_Factura_Venta PFV
LEFT JOIN Inventario_Viejo I
ON PFV.Id_Inventario = I.Id_Inventario
LEFT JOIN Inventario_Nuevo INV
ON PFV.Id_Inventario_Nuevo = INV.Id_Inventario_Nuevo
LEFT JOIN Producto P ON PFV.Id_Producto = P.Id_Producto
INNER JOIN Factura_Venta F 
ON PFV.Id_Factura_Venta=F.Id_Factura_Venta
INNER JOIN Cliente C 
ON F.Id_Cliente=C.Id_Cliente
WHERE PFV.Id_Factura_Venta ='.$id;

$oCon= new consulta();
$oCon->setQuery($query2);
$oCon->setTipo('Multiple');
$productos = $oCon->getData();
unset($oCon);

if(count($productos)==0){
    $query22 = 'SELECT 
    IFNULL(CONCAT(P.Principio_Activo, " ", P.Cantidad,"", P.Unidad_Medida, " " , P.Presentacion, "\n", P.Invima, " CUM:", P.Codigo_Cum), CONCAT(P.Nombre_Comercial, " LAB-", P.Laboratorio_Comercial)) as producto, 
    P.Id_Producto,
    IF(P.Laboratorio_Generico IS NULL,P.Laboratorio_Comercial,P.Laboratorio_Generico) as Laboratorio,
    P.Presentacion,
    P.Codigo_Cum as Cum, 
    PFV.Fecha_Vencimiento as Vencimiento, 
    PFV.Lote as Lote, 
    PFV.Id_Inventario as Id_Inventario,
    PFV.Precio_Venta as Costo_unitario,
    PFV.Cantidad as Cantidad,
    PFV.Precio_Venta as PrecioVenta,
    (PFV.Cantidad * PFV.Precio_Venta*(1-(PFV.Descuento/100)) ) as Subtotal,
    PFV.Id_Producto_Factura_Venta as idPFV,
    (CASE  
      WHEN P.Gravado = "Si" AND C.Impuesto="Si" THEN "19%" 
      ELSE "0%" 
    END) as Impuesto,
    CONCAT(PFV.Impuesto,"%") as Impuesto
    FROM Producto_Factura_Venta PFV
    LEFT JOIN Producto P ON PFV.Id_Producto = P.Id_Producto
    INNER JOIN Factura_Venta F 
    ON PFV.Id_Factura_Venta=F.Id_Factura_Venta
    INNER JOIN Cliente C 
    ON F.Id_Cliente=C.Id_Cliente
    WHERE PFV.Id_Factura_Venta ='.$id;
    
    $oCon= new consulta();
    $oCon->setQuery($query22);
    $oCon->setTipo('Multiple');
    $productos = $oCon->getData();
    unset($oCon);
}


/*
$query3 = 'SELECT * FROM `Nota_Credito` WHERE `Id_Factura` =  '.$id ;

$oCon= new consulta();
$oCon->setQuery($query3);
$oCon->setTipo('Multiple');
$notasCredito = $oCon->getData();
unset($oCon);
*/

// total para la suma de las notas credito
$query4 = 'SELECT SUM(Descuento_Factura) as TotalNC FROM Nota_Credito WHERE Id_Factura = '.$id ;
$oCon= new consulta();
$oCon->setQuery($query4);
// $totalNotasCredito = $oCon->getData();
$totalNotasCredito = ["TotalNC" => 0];
unset($oCon);

// consulta para total de facturas

$query5 = 'SELECT SUM((Cantidad * Precio_Venta*(1-(Descuento/100)) )) as TotalFac FROM Producto_Factura_Venta WHERE Id_Factura_Venta = '.$id ;
$oCon= new consulta();
$oCon->setQuery($query5);
$totalFactura = $oCon->getData();
unset($oCon);


$total_impuesto = 0;
foreach($productos as $prod){
    $total_impuesto += ($prod["Subtotal"]*(str_replace("%","",$prod["Impuesto"])/100));
}

$totalFactura["Iva"]=$total_impuesto;
$total = $totalFactura['TotalFac']+$total_impuesto;
$numero = number_format($total, 2, '.','');

$letras = NumeroALetras::convertir($numero)." PESOS MCTE." ;

$oItem = new complex('Remision','Id_Factura',$id);
$id_remision = $oItem->Id_Remision;
unset($oItem);

$actividades = [];

if ($id_remision) {
  
  $query='SELECT AR.*, F.Imagen,CONCAT_WS(" ",F.Nombres, F.Apellidos) as Funcionario,
  (CASE
      WHEN AR.Estado="Creacion" THEN CONCAT("1 ",AR.Estado)
      WHEN AR.Estado="Alistamiento" THEN CONCAT("2 ",AR.Estado)
      WHEN AR.Estado="Edicion" THEN CONCAT("2 ",AR.Estado)
      WHEN AR.Estado="Fase 1" THEN CONCAT("2 ",AR.Estado)
      WHEN AR.Estado="Fase 2" THEN CONCAT("3 ",AR.Estado)
      WHEN AR.Estado="Enviada" THEN CONCAT("4 ",AR.Estado)
      WHEN AR.Estado="Facturada" THEN CONCAT("5 ",AR.Estado)
      WHEN AR.Estado="Recibida" THEN CONCAT("5 ",AR.Estado)
      WHEN AR.Estado="Anulada" THEN CONCAT("2 ",AR.Estado)
  END) as Estado2
  FROM Actividad_Remision AR
  INNER JOIN Funcionario F
  On AR.Identificacion_Funcionario=F.Identificacion_Funcionario
  WHERE AR.Id_Remision='.$id_remision.' AND AR.Estado IN ("Facturada","Anulada")
  Order BY Estado2 ASC, Fecha ASC';

  $oCon= new consulta();
  $oCon->setQuery($query);
  $oCon->setTipo('Multiple');
  $actividades = $oCon->getData();
  unset($oCon);


  $query="SELECT '' as Id_Actividad_Remision, '' as Id_Remision, NC.Identificacion_Funcionario, NC.Fecha, CONCAT('Se realizo un Nota credito de la factura, el codigo de esta nota es ',NC.Codigo) as Detalles, 'Creacion' as Estado, F.Imagen,CONCAT_WS(' ',F.Nombres, F.Apellidos) as Funcionario, '' as Estado2  FROM Nota_Credito NC INNER JOIN Funcionario F ON NC.Identificacion_Funcionario=F.Identificacion_Funcionario WHERE NC.Id_Factura=".$id;
  $oCon= new consulta();
  $oCon->setQuery($query);
  $oCon->setTipo('Multiple');
  $actividades_nota = $oCon->getData();
  unset($oCon); 

  $actividades=array_merge($actividades,$actividades_nota);
}

$oItem = new complex("Resolucion", "Id_Resolucion", $dis['Id_Resolucion']);
$resolucion = $oItem->getData();
unset($oItem);


$resultado["Datos"]=$dis;
$resultado["actividades"]=$actividades;
$resultado["Productos"]=$productos;
$resultado["NotasCredito"]=$notasCredito;
$resultado["TotalNc"]=$totalNotasCredito;
$resultado["TotalFc"]=$totalFactura;
$resultado["letra"]=$letras;
$resultado["resolucion"]=$resolucion;

echo json_encode($resultado);


?>