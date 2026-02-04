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
$withHom = 0;

$query = 'SELECT FV.Id_Factura, FV.Cufe, FV.Id_Resolucion,
            FV.Codigo_Qr, FV.Fecha_Documento as Fecha , FV.Observacion_Factura as observacion, FV.Codigo as Codigo,FV.Condicion_Pago as Condicion_Pago , FV.Fecha_Pago as Fecha_Pago , FV.Tipo as tipo,
            C.Id_Cliente as IdCliente ,C.Nombre as NombreCliente, C.Direccion as DireccionCliente, C.Ciudad as CiudadCliente, C.Credito as CreditoCliente, C.Cupo as CupoCliente, (SELECT CONCAT_WS(" ",Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido, CONCAT("- ", UPPER(IF(Id_Regimen=1,"Contributivo","Subsidiado")))) FROM Paciente WHERE Id_Paciente=D.Numero_Documento) AS Nombre_Paciente, D.Numero_Documento, FV.Cuota, D.Codigo AS Cod_Dis, (SELECT Nombre FROM Tipo_Servicio WHERE Id_Tipo_Servicio = D.Tipo_Servicio) AS Tipo_Servicio
          FROM Factura FV
          INNER JOIN Dispensacion D ON FV.Id_Dispensacion = D.Id_Dispensacion
          INNER JOIN Cliente C
           ON FV.Id_Cliente = C.Id_Cliente    
          WHERE D.Id_Tipo_Servicio != 7 AND FV.Id_Factura = '.$id ;



$oCon= new consulta();
$oCon->setQuery($query);
$dis = $oCon->getData();
unset($oCon);

$query = 'SELECT FV.Id_Factura, FV.Cufe, FV.Id_Resolucion,
            FV.Codigo_Qr, FV.Fecha_Documento as Fecha , FV.Observacion_Factura as observacion, FV.Codigo as Codigo,FV.Condicion_Pago as Condicion_Pago , FV.Fecha_Pago as Fecha_Pago , FV.Tipo as tipo,
            C.Id_Cliente as IdCliente ,C.Nombre as NombreCliente, C.Direccion as DireccionCliente, C.Ciudad as CiudadCliente, C.Credito as CreditoCliente, C.Cupo as CupoCliente
          FROM Factura FV
          INNER JOIN Cliente C
           ON FV.Id_Cliente = C.Id_Cliente
          WHERE FV.Id_Factura_Asociada = '.$id ;



$oCon= new consulta();
$oCon->setQuery($query);
$hom = $oCon->getData();
unset($oCon);

if ($hom) { // ¿Tiene Homologo?
  $withHom = 1;

  $query2 = 'SELECT 
  CONCAT_WS(" ",P.Nombre_Comercial, P.Presentacion, P.Concentracion, " (", P.Principio_Activo,") ", P.Cantidad," ", P.Unidad_Medida ) as producto, 
  P.Invima,
  P.Id_Producto, 
  P.Codigo_Cum as Cum, 
  COALESCE(I.Fecha_Vencimiento, INV.Fecha_Vencimiento,PFV.Fecha_Vencimiento) as Vencimiento, 
  IFNULL(PD.Lote, PFV.Lote) as Lote, 
  IFNULL(PD.Id_Inventario,PD.Id_Inventario_Nuevo) as Id_Inventario,
  0 as Costo_unitario,
  PFV.Cantidad as Cantidad,
  PFV.Precio as Precio,
  PFV.Impuesto as Impuesto,
  PFV.Descuento as Descuento,
  PFV.Subtotal as Subtotal,
  PFV.Id_Producto_Factura as idPFV
 FROM Producto_Factura PFV 
 LEFT JOIN Producto_Dispensacion PD
 ON PD.Id_Producto_Dispensacion = PFV.Id_Producto_Dispensacion 
 INNER JOIN Producto P 
 ON P.Id_Producto = PFV.Id_Producto
 LEFT JOIN Inventario_Viejo I
 ON I.Id_Inventario = PD.Id_Inventario 
 LEFT JOIN Inventario_Nuevo INV
 ON INV.Id_Inventario_Nuevo = PD.Id_Inventario_Nuevo
 WHERE PFV.Id_Factura =  '.$hom['Id_Factura'] ;

$oCon= new consulta();
$oCon->setQuery($query2);
$oCon->setTipo('Multiple');
$productos_hom = $oCon->getData();
unset($oCon);

$subtotal_hom = 0;
$descuentos_hom = 0;
$iva_hom = 0;

foreach ($productos_hom as $prod) {
  $subtotal_hom += $prod['Subtotal'];
  $descuentos_hom += $prod['Descuento'] * $prod['Cantidad'];
  $iva_hom += $prod['Subtotal'] * ($prod['Impuesto']/100);
}

$total_hom = $subtotal_hom - $descuentos_hom + $iva_hom;

$letras_hom = NumeroALetras::convertir(number_format($total_hom,2,".",""));
  
  
  $oItem = new complex("Resolucion", "Id_Resolucion", $hom["Id_Resolucion"]);
  $resolucion_hom = $oItem->getData();
  unset($oItem); 
  $resolucion_hom = array_map("utf8_encode", $resolucion_hom);

}


$query3 = 'SELECT 
            CONCAT_WS(" ",P.Nombre_Comercial, P.Presentacion, P.Concentracion, " (", P.Principio_Activo,") ", P.Cantidad," ", P.Unidad_Medida ) as producto, 
            P.Invima,
            P.Id_Producto, 
            P.Codigo_Cum as Cum, 
            COALESCE(I.Fecha_Vencimiento, INV.Fecha_Vencimiento,PFV.Fecha_Vencimiento) as Vencimiento, 
            IFNULL(PD.Lote, PFV.Lote) as Lote, 
            IFNULL(PD.Id_Inventario,PD.Id_Inventario_Nuevo) as Id_Inventario,
            0 as Costo_unitario,
            PFV.Cantidad as Cantidad,
            PFV.Precio as Precio,
            PFV.Impuesto as Impuesto,
            PFV.Descuento as Descuento,
            PFV.Subtotal as Subtotal,
            PFV.Id_Producto_Factura as idPFV
           FROM Producto_Factura PFV 
           LEFT JOIN Producto_Dispensacion PD
           ON PD.Id_Producto_Dispensacion = PFV.Id_Producto_Dispensacion 
           INNER JOIN Producto P 
           ON P.Id_Producto = PFV.Id_Producto
           LEFT JOIN Inventario_Viejo I
           ON I.Id_Inventario = PD.Id_Inventario 
           LEFT JOIN Inventario_Nuevo INV
           ON INV.Id_Inventario_Nuevo = PD.Id_Inventario_Nuevo WHERE PFV.Id_Factura =  '.$id ;

$oCon= new consulta();
$oCon->setQuery($query3);
$oCon->setTipo('Multiple');
$productos = $oCon->getData();
unset($oCon);

$subtotal = 0;
$descuentos = 0;
$iva = 0;

foreach ($productos as $prod) {
  $subtotal += $prod['Subtotal'];
  $descuentos += $prod['Descuento'] * $prod['Cantidad'];
  $iva += $prod['Subtotal'] * ($prod['Impuesto']/100);
}

$total = $subtotal - $descuentos + $iva - $dis['Cuota'];

$letras = NumeroALetras::convertir(number_format($total,2,".",""));

$oItem = new complex("Resolucion", "Id_Resolucion", $dis["Id_Resolucion"]);
$resolucion_fact = $oItem->getData();
unset($oItem);

$resolucion_fact = array_map("utf8_encode", $resolucion_fact);

$resultado["Datos"]=$dis;
$resultado["Datos_hom"]=$hom;
$resultado["Productos"]=$productos;
$resultado["Productos_hom"]=$productos_hom;
$resultado["TotalFc"]=$total;
$resultado["TotalFc_hom"]=$total_hom;
$resultado["letra"]=$letras;
$resultado["letra_hom"]=$letras_hom;
$resultado["resolucion_fact"]=$resolucion_fact;
$resultado["resolucion_hom"]=$resolucion_hom;
$resultado["withHom"]=$withHom;


$query = "SELECT AF.*,  CONCAT_WS(' ',FC.Nombres,FC.Apellidos) AS Funcionario
                FROM Actividad_Factura AF 
                INNER JOIN Funcionario FC ON FC.Identificacion_Funcionario = AF.Id_Funcionario 
                WHERE AF.Factura = $id ";
          
$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$actividades = $oCon->getData();
unset($oCon);

$resultado['actividades'] = $actividades;

echo json_encode($resultado);


// $query = "SELECT F.Fecha_Documento AS Fecha,
//                 'Facturada' AS Estado, 
//                 CONCAT_WS(' ',FC.Nombres,FC.Apellidos) AS Funcionario, 
//                 FC.Imagen, CONCAT('Se facturo con codigo: ', F.Codigo) AS Detalles 
//                 FROM Factura F 
//                 INNER JOIN Funcionario FC ON F.Id_Funcionario = FC.Identificacion_Funcionario 
//                 WHERE F.Id_Factura = $id";



?>