<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

 $id = ( isset( $_REQUEST['id'] ) ? (int) $_REQUEST['id'] : 0 );

 if ($id === 0) {
     http_response_code(400);
     echo json_encode(['error' => 'El id de la dispensacion es obligatorio.']);
     exit;
 }

$query = "SELECT 
                DATE_FORMAT(D.Fecha_Actual, '%Y') as Year, D.Codigo as Codigo, Dep.Nombre, P.EPS, P.Nit, DC.Id_Cliente, Dep.Id_Departamento , CONCAT(P.Id_Paciente , ' - ', P.Primer_Nombre, ' ', P.Primer_Apellido,  ' - Regimen ' , R.Nombre ) as Paciente, 
                (SELECT CONCAT(S.Nombre,' - ',T.Nombre) as Nombre FROM Tipo_Servicio T INNER JOIN Servicio S on T.Id_Servicio=S.Id_Servicio WHERE T.Id_Tipo_Servicio = D.Id_Tipo_Servicio) AS Tipo_Dispensacion, 
                (SELECT Nombre FROM Tipo_Servicio WHERE Id_Tipo_Servicio = D.Id_Tipo_Servicio) AS Tipo_Servicio,
                (SELECT Nombre FROM Servicio WHERE Id_Servicio = D.Id_Servicio) AS Servicio,
                D.Id_Tipo_Servicio,
                 P.Id_Regimen, D.Cuota, D.Id_Punto_Dispensacion,
                 D2.Codigo as Dis_Pendientes
          FROM `Dispensacion` D 
          INNER JOIN Paciente P 
            ON P.Id_Paciente = D.Numero_Documento
          INNER JOIN (SELECT Id_Punto_Dispensacion, Departamento FROM Punto_Dispensacion) PT
            ON PT.Id_Punto_Dispensacion = D.Id_Punto_Dispensacion 
          INNER JOIN Departamento Dep 
            ON Dep.Id_Departamento = P.Id_Departamento 
          INNER JOIN Departamento_Cliente DC 
            ON Dep.Id_Departamento = DC.Id_Departamento
          INNER JOIN Regimen R
           ON P.Id_Regimen = R.Id_Regimen
           Left join Dispensacion D2 on D2.Id_Dispensacion = D.Id_Dispensacion_Pendientes
          WHERE D.Id_Dispensacion = ".$id;

$oCon= new consulta();
//$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$nombre = $oCon->getData();
unset($oCon);

if (!is_array($nombre) || empty($nombre)) {
    http_response_code(404);
    echo json_encode(['error' => 'No se encontro informacion para la dispensacion solicitada.']);
    exit;
}

$nitCliente = isset($nombre['Nit']) && $nombre['Nit'] !== '' ? (int) $nombre['Nit'] : 0;
$idCliente = isset($nombre['Id_Cliente']) && $nombre['Id_Cliente'] !== '' ? (int) $nombre['Id_Cliente'] : 0;

if ($nitCliente === 0 && $idCliente === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'La dispensacion no tiene un cliente asociado.']);
    exit;
}

// SE AGREGÓ EL NUEVO TIPO DE DISPENSACION COHORTES -- KENDRY 06/05/2019
// CAMBIO EN LAS CONDICIONES DE LAS CONSULTAS DEBIDO AL NUEVO MODELO DE DISPENSACION -- FRANKLIN 27-08-2019

    
    if (   (strtolower($nombre["Tipo_Servicio"]) == "evento")
        || (strtolower($nombre["Tipo_Servicio"]) == "cohortes")  
        || (strtolower($nombre["Tipo_Servicio"]) == "mipres tutela"      &&          ($nombre["Nit"]=='901097473' || ($nombre["Nit"]=='900156264' && (INT)$nombre["Year"]>=2019 ))) 
        || (strtolower($nombre["Tipo_Servicio"]) == "ctc"                &&          ($nombre["Nit"]=='901097473' || ($nombre["Nit"]=='900156264' && (INT)$nombre["Year"]>=2019 ))) 
        || (strtolower($nombre["Tipo_Servicio"]) == "mipres subsidiado"  &&          ($nombre["Nit"]=='901097473' || ($nombre["Nit"]=='900156264' && (INT)$nombre["Year"]>=2019 ))) 
        || (strtolower($nombre["Tipo_Servicio"]) == "mipres"             &&          ($nombre["Nit"]=='900226715' || ($nombre["Nit"]=='901097473' || ($nombre["Nit"]=='900156264' && (INT)$nombre["Year"]>=2019 ))))   
        || (strtolower($nombre["Servicio"]) == "no pos")                 &&          ($nombre["Id_Regimen"] == 1)
        ||  (strtolower($nombre["Servicio"]) == "positiva"
        ||  (strtolower($nombre["Servicio"]) == "axa colpatria"))                 
        
    ) {
            
      // busco cliente
      $idClienteFactura = $nitCliente ?: $idCliente;
      $query1 = 'SELECT Id_Cliente as IdClienteFactura, Nombre as ClienteFactura, Condicion_Pago as CondicionPago FROM Cliente WHERE Id_Cliente ='.$idClienteFactura;
    
    } else {
           
      // busco cliente
      $idClienteFactura = $idCliente ?: $nitCliente;
      $query1 = 'SELECT Id_Cliente as IdClienteFactura, Nombre as ClienteFactura, Condicion_Pago as CondicionPago FROM Cliente WHERE Id_Cliente ='.$idClienteFactura;
      
    }
//echo $query1; exit;
$oCon= new consulta();
$oCon->setQuery($query1);
$factura = $oCon->getData();
unset($oCon);

if (!is_array($factura)) {
    $factura = [
        'IdClienteFactura' => $idClienteFactura,
        'ClienteFactura' => '',
        'CondicionPago' => ''
    ];
}


// busco homologo
$idClienteHomologo = $nitCliente ?: $idCliente;
$query2 = 'SELECT Id_Cliente as IdClienteHomologo, Nombre as ClienteHomologo , Condicion_Pago as CondicionPagoHomologo FROM Cliente WHERE Id_Cliente ='.$idClienteHomologo;
$oCon= new consulta();
$oCon->setQuery($query2);
$homologo = $oCon->getData();
unset($oCon);

if (!is_array($homologo)) {
    $homologo = [
        'IdClienteHomologo' => $idClienteHomologo,
        'ClienteHomologo' => '',
        'CondicionPagoHomologo' => ''
    ];
}


 
$band_homologo = false;
if(strtolower($nombre["Tipo_Servicio"]) == "positiva" || strtolower($nombre["Tipo_Servicio"]) == "axa colpatria" || strtolower($nombre["Tipo_Servicio"]) == "FARMACIA"){
      $band_homologo = false;
      $query3 = "SELECT
    T.*,  
    ( 
        CASE
            WHEN PRG.Codigo_Cum IS NOT NULL THEN PRG.Precio
            WHEN T.Precio_Venta_Factura IS NOT NULL THEN T.Precio_Venta_Factura
            ELSE 0
        END
    ) AS Precio_Venta_Factura,
    IF( PRG.Codigo_Cum IS NOT NULL, 'Si', 'No' ) AS Regulado, 
    IFNULL(PRG.Precio, -1) as Precio_Regulacion
  FROM
    (
        SELECT
            CONCAT_WS( ' ', p.Nombre_Comercial, p.Presentacion, p.Concentracion, '(', p.Principio_Activo, ')', p.Cantidad, p.Unidad_Medida ) AS Nombre,
            0 AS Costo_unitario,
            PD.Lote AS Lote,
            IFNULL( i.Id_Inventario, INV.Id_Inventario_Nuevo ) AS Id_Inventario,
             p.Codigo_Cum AS Cum,
            p.Invima AS Invima,
            IFNULL( i.Fecha_Vencimiento, INV.Fecha_Vencimiento ) AS Fecha_Vencimiento,
            p.Laboratorio_Generico AS Laboratorio_Generico,
            p.Laboratorio_Comercial AS Laboratorio_Comercial,
            p.Presentacion AS Presentacion,
            PD.Cantidad_Formulada AS Cantidad,
            PD.Id_Producto_Dispensacion,
            p.Gravado AS Gravado,
            p.Id_Producto,
            REPLACE (p.Codigo_Cum, '-', '') AS Cum_Medicamento,
            '0' AS Descuento,
            IF( p.Gravado = 'Si' AND  '$factura[IdClienteFactura]' != 830074184,
                0.19, 0 ) AS Impuesto,
            '0' AS Subtotal,
            IFNULL(PNP.Precio, 0) AS Precio_Venta_Factura,
            0 AS Precio,
            0 AS Iva,
            0 AS Total_Descuento,
            p.Codigo_Cum,
            '' as Cum_Homologo,
            '' AS Id_Producto_Hom,
            0 as Precio_Homologo,
            '' as Detalle_Homologo,
            PNP.Id_Producto_Contrato,
            PNP.Precio as Precio_Tarifa,
            PD.Costo as Costo_Dispensacion,
            IF( PNP.Id_Producto_Contrato IS NULL, 1, 0 ) AS Registrar
        FROM
            Producto_Dispensacion AS PD
            INNER JOIN Producto p ON p.Id_Producto = PD.Id_Producto
            -- LEFT JOIN Costo_Promedio CP on CP.Id_Producto = p.Id_Producto
            LEFT JOIN Inventario_Viejo i ON i.Id_Inventario = PD.Id_Inventario
            LEFT JOIN Inventario_Nuevo INV ON INV.Id_Inventario_Nuevo = PD.Id_Inventario_Nuevo
            LEFT JOIN(
                SELECT PNP.* 
                FROM
                    Producto_Contrato PNP
                INNER JOIN Contrato LPN ON LPN.Id_Contrato = PNP.Id_Contrato
                WHERE
                     LPN.Tipo_Contrato = 'Eps'
                    AND LPN.Id_Cliente = {$nitCliente}
                ) PNP ON PD.Cum = PNP.Cum           
        WHERE
            PD.Id_Dispensacion =  '$id'
            AND PD.Cantidad_Formulada>0
    ) T
    LEFT JOIN(
        SELECT 
            Precio,
            Codigo_Cum,
            REPLACE (Codigo_Cum, '-', '') AS Cum
        FROM
            Precio_Regulado
        GROUP BY
            Codigo_Cum
    ) PRG ON T.Codigo_Cum=PRG.Codigo_Cum";

    // echo $query3; exit;
} elseif (strtolower($nombre["Tipo_Servicio"]) == "evento") {
  //busco los productos 
    $query3 = 'SELECT T.*,(
        CASE
          WHEN T.Precio_Venta_Factura IS NOT NULL THEN T.Precio_Venta_Factura
          WHEN PRG.Codigo_Cum IS NOT NULL THEN PRG.Precio
          ELSE 0
        END
        ) AS Precio_Venta_Factura,IF(PRG.Codigo_Cum IS NOT NULL, "Si", "No") AS Regulado  FROM (SELECT  
      CONCAT_WS(" ", p.Nombre_Comercial, p.Presentacion, p.Concentracion, " (",p.Principio_Activo ,") ", p.Cantidad, p.Unidad_Medida ) as Nombre ,
        0 as Costo_unitario,
      IFNULL(i.Lote,INV.Lote) AS Lote,
      IFNULL(i.Id_Inventario,INV.Id_Inventario_Nuevo) as Id_Inventario,
      IFNULL(i.Fecha_Vencimiento,INV.Fecha_Vencimiento) as Fecha_Vencimiento,
      p.Codigo_Cum,
      p.Codigo_CUM as Cum,
      p.Invima as Invima,
      p.Laboratorio_Generico as Laboratorio_Generico,
      p.Laboratorio_Comercial as Laboratorio_Comercial,
      p.Presentacion as Presentacion,
      PD.Cantidad_Formulada as Cantidad,
      PD.Id_Producto_Dispensacion,
      p.Gravado as Gravado,
      p.Id_Producto,REPLACE(p.Codigo_Cum,"-","")  as Cum_Medicamento,
      "0" as Descuento,
      IF(p.Gravado = "Si" AND '.$factura['IdClienteFactura'].' != 830074184, 0.19, 0) as Impuesto,
      "0" as Subtotal,
      0 as Precio,
      IFNULL(PE.Precio,0) as Precio_Venta_Factura,
      0 as Iva,
      0 as Total_Descuento,
      IF(PE.Id_Producto_Evento IS NULL, 1, 0) AS Registrar
      FROM Producto_Dispensacion as PD 
      LEFT JOIN Producto_Evento PE ON PD.Cum = PE.Codigo_Cum AND PE.Nit_EPS = '.$nitCliente.'
      INNER JOIN Producto p on p.Id_Producto=PD.Id_Producto
      LEFT JOIN Inventario_Viejo i ON i.Id_Inventario = PD.Id_Inventario
      LEFT JOIN Inventario_Nuevo INV ON INV.Id_Inventario_Nuevo = PD.Id_Inventario_Nuevo
      WHERE PD.Id_Dispensacion =  '.$id.' 
      AND PD.Cantidad_Formulada>0) T  LEFT JOIN (SELECT Precio, Codigo_Cum,  REPLACE(Codigo_Cum,"-","")  as Cum FROM Precio_Regulado group  BY Codigo_Cum) PRG  ON T.Codigo_Cum=PRG.Codigo_Cum ' ;

} else if (strtolower($nombre["Tipo_Servicio"]) == "cohortes"){
 
    $query3 = 'SELECT T.*,(
      CASE
        WHEN PRG.Codigo_Cum IS NOT NULL THEN PRG.Precio
        WHEN T.Precio_Venta_Factura IS NOT NULL THEN T.Precio_Venta_Factura
        ELSE 0
      END
      ) AS Precio_Venta_Factura,IF(PRG.Codigo_Cum IS NOT NULL, "Si", "No") AS Regulado  FROM (SELECT    
  CONCAT_WS(" ", p.Nombre_Comercial, p.Presentacion, p.Concentracion, " (",p.Principio_Activo ,") ", p.Cantidad, p.Unidad_Medida ) as Nombre ,

    0 as Costo_unitario,
  IFNULL(i.Lote,INV.Lote) AS Lote,
  IFNULL(i.Id_Inventario,INV.Id_Inventario_Nuevo) as Id_Inventario,
  IFNULL(i.Fecha_Vencimiento,INV.Fecha_Vencimiento) as Fecha_Vencimiento,

  p.Codigo_Cum,
  p.Codigo_CUM as Cum,
  p.Invima as Invima,
  p.Laboratorio_Generico as Laboratorio_Generico,
  p.Laboratorio_Comercial as Laboratorio_Comercial,
  p.Presentacion as Presentacion,
  PD.Cantidad_Formulada as Cantidad,
  PD.Id_Producto_Dispensacion,
  p.Gravado as Gravado,
  p.Id_Producto,REPLACE(p.Codigo_Cum,"-","")  as Cum_Medicamento,
  "0" as Descuento,
  IF(p.Gravado = "Si" AND '.$factura['IdClienteFactura'].' != 830074184, 0.19, 0) as Impuesto,
  "0" as Subtotal,
  IFNULL(PE.Precio,0) as Precio_Venta_Factura,
  0 as Precio,
  0 as Iva,
  0 as Total_Descuento,
  IF(PE.Id_Producto_Cohorte IS NULL, 1, 0) AS Registrar
  FROM Producto_Dispensacion as PD 
  LEFT JOIN Producto_Cohorte PE ON PD.Cum = PE.Codigo_Cum AND PE.Nit_EPS = '.$nitCliente.'
  INNER JOIN Producto p on p.Id_Producto=PD.Id_Producto
  LEFT JOIN Inventario_Viejo i ON i.Id_Inventario = PD.Id_Inventario
  LEFT JOIN Inventario_Nuevo INV ON INV.Id_Inventario_Nuevo = PD.Id_Inventario_Nuevo
  WHERE PD.Id_Dispensacion =  '.$id.'AND PD.Cantidad_Formulada>0) T  LEFT JOIN (SELECT Precio, Codigo_Cum, REPLACE(Codigo_Cum,"-","") as Cum FROM Precio_Regulado GROUP BY Codigo_Cum) PRG  ON T.Codigo_Cum=PRG.Codigo_Cum' ;


} elseif ((strtolower($nombre["Servicio"]) == "no pos" && $nombre["Id_Regimen"] == 1)) {

 
  $query3 = 'SELECT T.*,(
    CASE
      WHEN PRG.Codigo_Cum IS NOT NULL THEN PRG.Precio
      WHEN T.Precio_Venta_Factura IS NOT NULL THEN T.Precio_Venta_Factura
      ELSE 0
    END
    ) AS Precio_Venta_Factura,IF(PRG.Codigo_Cum IS NOT NULL, "Si", "No") AS Regulado  FROM (SELECT  
      CONCAT_WS(" ", p.Nombre_Comercial, p.Presentacion, p.Concentracion, " (",p.Principio_Activo ,") ", p.Cantidad, p.Unidad_Medida ) as Nombre ,

        0 as Costo_unitario,
      IFNULL(i.Lote,INV.Lote) AS Lote,
      IFNULL(i.Id_Inventario,INV.Id_Inventario_Nuevo) as Id_Inventario,
      IFNULL(i.Fecha_Vencimiento,INV.Fecha_Vencimiento) as Fecha_Vencimiento,
      p.Codigo_Cum,
      p.Codigo_CUM as Cum,
      p.Invima as Invima,
      p.Laboratorio_Generico as Laboratorio_Generico,
      p.Laboratorio_Comercial as Laboratorio_Comercial,
      p.Presentacion as Presentacion,
      PD.Cantidad_Formulada as Cantidad,
      PD.Id_Producto_Dispensacion,
      p.Gravado as Gravado,
      p.Id_Producto,REPLACE(p.Codigo_Cum,"-","")  as Cum_Medicamento,
      "0" as Descuento,
      IF(p.Gravado = "Si" AND '.$factura['IdClienteFactura'].' != 830074184, 0.19, 0) as Impuesto,
      "0" as Subtotal,
      IFNULL( PNP.Precio,0) AS Precio_Venta_Factura,
      0 as Precio,
      0 as Iva,
      0 as Total_Descuento,
      PNP.Id_Producto_NoPos,
      IF(PNP.Id_Producto_NoPos IS NULL, 1, 0) AS Registrar
      FROM Producto_Dispensacion as PD 
      INNER JOIN Producto p on p.Id_Producto=PD.Id_Producto
      LEFT JOIN Inventario_Viejo i ON i.Id_Inventario = PD.Id_Inventario
      LEFT JOIN Inventario_Nuevo INV ON INV.Id_Inventario_Nuevo = PD.Id_Inventario_Nuevo
      LEFT JOIN (SELECT PNP.* FROM Producto_NoPos PNP 
      INNER JOIN Lista_Producto_Nopos LPN ON LPN.Id_Lista_Producto_Nopos = PNP.Id_Lista_Producto_Nopos
      WHERE LPN.Id_Cliente ='.($nitCliente ?: $idCliente).') PNP ON PD.Cum = PNP.Cum
      WHERE PD.Id_Dispensacion =  '.$id.' AND PD.Cantidad_Formulada>0) T  lEFT JOIN (SELECT Precio, Codigo_Cum,  REPLACE(Codigo_Cum,"-","")  as Cum FROM Precio_Regulado group  BY Codigo_Cum) PRG  ON T.Codigo_Cum=PRG.Codigo_Cum' ;
} else {
    
  if (strtolower($nombre["Tipo_Servicio"]) == "farmacia") {
      $band_homologo = false;
  } elseif (($nombre["Nit"]=='900156264' || $nombre["Nit"]=='900226715') && (INT)$nombre["Year"]>=2019) {
      $band_homologo = false;
  } else {
      $band_homologo = true; 
  } 
  
  //busco los productos 

    $query3 = 'SELECT T.*,(
        CASE
          WHEN PRG.Codigo_Cum IS NOT NULL THEN PRG.Precio
          WHEN T.Precio_Venta_Factura IS NOT NULL THEN T.Precio_Venta_Factura
          ELSE 0
        END
        ) AS Precio_Venta_Factura,
        IF(PRG.Codigo_Cum IS NOT NULL, "Si", "No") AS Regulado  FROM (SELECT  
      CONCAT_WS(" ", p.Nombre_Comercial, p.Presentacion, p.Concentracion, " (",p.Principio_Activo ,") ", p.Cantidad, p.Unidad_Medida ) as Nombre ,

        0 as Costo_unitario,

      PD.Lote as Lote,
      IFNULL(i.Id_Inventario,INV.Id_Inventario_Nuevo) as Id_Inventario,
      PD.Cum as Cum,
      p.Invima as Invima,
      IFNULL(i.Fecha_Vencimiento,INV.Fecha_Vencimiento) as Fecha_Vencimiento,
      p.Laboratorio_Generico as Laboratorio_Generico,
      p.Laboratorio_Comercial as Laboratorio_Comercial,
      p.Presentacion as Presentacion,
      PD.Cantidad_Formulada as Cantidad,
      PD.Id_Producto_Dispensacion,
      p.Gravado as Gravado,
      p.Id_Producto,REPLACE(p.Codigo_Cum,"-","")  as Cum_Medicamento,
      "0" as Descuento,
      IF(p.Gravado = "Si" AND '.$factura['IdClienteFactura'].' != 830074184, 0.19, 0) as Impuesto,
      "0" as Subtotal,
      COALESCE(PC.Precio, PNP.Precio, 0) AS Precio_Venta_Factura,
      IFNULL(IF(PRGH.Codigo_Cum IS NOT NULL,PRGH.Precio,PNP.Precio_Homologo),0) as Precio,
      0 as Iva,
      0 as Total_Descuento,
      PRG.Codigo_Cum,
      PNP.Cum_Homologo,
      IF(PNP.Cum_Homologo IS NOT NULL,(SELECT Id_Producto FROM Producto WHERE Codigo_Cum = PNP.Cum_Homologo LIMIT 1),"") AS Id_Producto_Hom,
      IF(PRGH.Codigo_Cum IS NOT NULL,PRGH.Precio,PNPH.Precio) AS Precio_Homologo,
      PNP.Detalle_Homologo,
      PNP.Id_Producto_NoPos,
      IF(PNP.Id_Producto_NoPos IS NULL, 1, 0) AS Registrar
      FROM Producto_Dispensacion as PD 
      INNER JOIN Producto p on p.Id_Producto=PD.Id_Producto
      LEFT JOIN Inventario_Viejo i ON i.Id_Inventario = PD.Id_Inventario
      LEFT JOIN Inventario_Nuevo INV ON INV.Id_Inventario_Nuevo = PD.Id_Inventario_Nuevo
      LEFT JOIN Precio_Regulado PRG ON PD.Cum = PRG.Codigo_Cum
      LEFT JOIN (
          SELECT PC.*
          FROM Tipo_Servicio_Contrato TSC
          INNER JOIN Contrato C ON TSC.Id_Contrato = C.Id_Contrato
          INNER JOIN Producto_Contrato PC ON C.Id_Contrato = PC.Id_Contrato
          WHERE TSC.Id_Tipo_Servicio = '.$nombre["Id_Tipo_Servicio"].'
            AND C.Id_Cliente = '.($nitCliente ?: $idCliente).'
      ) PC ON PD.Cum = PC.Cum

      LEFT JOIN (SELECT PNP.* FROM Producto_NoPos PNP  INNER JOIN Lista_Producto_Nopos LPN ON LPN.Id_Lista_Producto_Nopos = PNP.Id_Lista_Producto_Nopos
      WHERE LPN.Id_Cliente ='.($nitCliente ?: $idCliente).') PNP ON PD.Cum = PNP.Cum
      LEFT JOIN (SELECT PNP.* FROM Producto_NoPos PNP 
                  INNER JOIN Lista_Producto_Nopos LPN ON LPN.Id_Lista_Producto_Nopos = PNP.Id_Lista_Producto_Nopos
                  WHERE LPN.Id_Cliente ='.($nitCliente ?: $idCliente).') PNPH ON PNP.Cum_Homologo = PNPH.Cum
      LEFT JOIN Precio_Regulado PRGH ON PNP.Cum_Homologo = PRGH.Codigo_Cum
      WHERE PD.Id_Dispensacion =  '.$id.' AND PD.Cantidad_Formulada>0) T  
      LEFT JOIN (SELECT Precio, Codigo_Cum, REPLACE(Codigo_Cum,"-","")  as Cum FROM Precio_Regulado group  BY Codigo_Cum) PRG ON T.Codigo_Cum=PRG.Codigo_Cum
      LEFT JOIN (SELECT Precio, Codigo_Cum,  REPLACE(Codigo_Cum,"-","") as Cum FROM Precio_Regulado group  BY Codigo_Cum) PRGH ON T.Cum_Homologo=PRGH.Codigo_Cum';
}

//echo $query3; exit;
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query3);
$productos = $oCon->getData();
$productosHom = $productos;
unset($oCon);

$i=-1;
foreach($productos as $lista){$i++;
$productos[$i]['Impuesto'] = (FLOAT) $lista['Impuesto'];
$productos[$i]['producto'] = $productos[$i];
$productos[$i]['Impuesto'] = (FLOAT) $lista['Impuesto'];
$productosHom[$i]['producto'] = $productos[$i];
$productosHom[$i]['Id_Producto'] = $productos[$i]['Id_Producto_Hom']; // Capturar el Id_Producto de los Homologos

}


 // busco si el cliente tiene contrato
$query5 = 'SELECT Id_Contrato ,count(*) as conteo FROM `Contrato` where Id_Cliente ='.$homologo['IdClienteHomologo'] ;
$oCon= new consulta();
$oCon->setQuery($query5);
$contratoCliente= $oCon->getData();
unset($oCon);

$preciosTabla=[];
$posiciones=[];
$resultado['productos'] = $productos;
$resultado['productoHomologo'] =$productosHom;
$resultado['factura'] = $factura;
$resultado['homologo'] = $homologo;
$resultado['encabezado'] = $nombre;
$resultado['es_homologo'] = $band_homologo;


echo json_encode($resultado);


?>
