<?php
  header('Access-Control-Allow-Origin: *');
  header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
  date_default_timezone_set('America/Bogota');
  include_once('../../class/class.http_response.php');
  include_once('../../class/class.querybasedatos.php');
  require_once('../../class/class.configuracion.php');
  //include_once('../../class/class.contabilizar.php');
  require_once('../../class/class.qr.php'); 
  require_once('../../config/start.inc.php');

   //$contabilizar = new Contabilizar();
    $queryObj = new QueryBaseDatos();
    $http_response = new HttpResponse();
    $response = array();
     $query="SELECT 
     P.Nombre_Comercial,
     P.Codigo_Cum,
     PF.Precio, PF.Id_Producto
 FROM
     Producto_Factura PF
         INNER JOIN
     Factura F ON PF.Id_Factura = F.Id_Factura
     INNER JOIN Producto P ON PF.Id_Producto=P.Id_Producto
 WHERE
     F.Id_Funcionario = 1095815196
         AND F.Estado_Factura != 'Anulada'
         AND DATE(Fecha_Documento) between '2019-07-01' AND '2019-08-06' AND PF.Subtotal=0 group by PF.Id_Producto";                       
    $queryObj->SetQuery($query);  
    $productos_facturacion = $queryObj->ExecuteQuery('multiple');  

   


    foreach ($productos_facturacion as  $value) {
     
     
      $precio = ($id);

  
    if($datos_dis){
        $factura=getFactura($datos_dis);

       
        if($factura){
          $dis_facturadas.=ObtenerCodigo($id).',';
            $homologo=GetHomologo($datos_dis["Nit"]);
            $band_homologo = false;
            $productosHom=[];
            $productos=getProductos($factura,$datos_dis,$id);

               
            $encabezadoFactura['Fecha_Documento']=date("Y-m-d H:i:s");
            $encabezadoFactura['Id_Cliente']=$factura['Id_Cliente'];
            $encabezadoFactura['Id_Funcionario']=1095815196;
            $encabezadoFactura['Estado_Factura']="Sin Cancelar";
            $encabezadoFactura['Id_Dispensacion']= $id;
            $encabezadoFactura['Condicion_Pago']=$factura['Condicion_Pago'];
            $encabezadoFactura['Cuota']=$datos_dis['Cuota'];
            $encabezadoFactura['Fecha_Pago']=ObtenerFechaPago($factura['Condicion_Pago']);
            $encabezadoFactura['Tipo']='Factura';
            $id_factura_asociada = '';

           
      
            $factura = guardarFactura($encabezadoFactura, $productos, "Factura",'Factura');
            
            if($band_homologo){
              $encabezadoFactura['Id_Cliente']=$homologo['Id_Cliente'];
              $encabezadoFactura['Condicion_Pago']=$homologo['Condicion_Pago'];
              $encabezadoFactura['Fecha_Pago']=ObtenerFechaPago($homologo['Condicion_Pago']);
              $homologo = guardarFactura($encabezadoFactura, $productosHom, "Factura",'Factura');
            }
            
            if($factura[0] != false && $homologo[0] != false){
          
              $oItem = new complex("Dispensacion","Id_Dispensacion",$encabezadoFactura['Id_Dispensacion']);
              $dispensacion = $oItem->getData();
              $oItem->Id_Factura = $factura[1];
              $oItem->Fecha_Facturado = date('Y-m-d H:i:s');
              $oItem->Estado_Facturacion = "Facturada";
              $oItem->Facturador_Asignado = 1095815196;
              $oItem->Identificacion_Facturador = 1095815196;
              $oItem->save();
              unset($oItem);
              
              $resultado['titulo'] = "Creacion exitosa";
              $resultado['mensaje'] = "Se ha guardado correctamente la Factura con codigo: ". $factura[0] . " Y la Homologacion con codigo: ".$homologo[0];
              $resultado['tipo'] = "success";
              $resultado['Id'] = $factura[1];
              $resultado['Fact'] = 'Homologo';
          }elseif($factura[0] != false){
              
              $oItem = new complex("Dispensacion","Id_Dispensacion",$encabezadoFactura['Id_Dispensacion']);
              $dispensacion = $oItem->getData();
              $oItem->Id_Factura = $factura[1];
              $oItem->Fecha_Facturado = date('Y-m-d H:i:s');
              $oItem->Estado_Facturacion = "Facturada";
              $oItem->Facturador_Asignado = 1095815196;
              $oItem->Identificacion_Facturador = 1095815196;
              $oItem->save();
              unset($oItem);
              
              $resultado['titulo'] = "Creacion exitosa";
              $resultado['mensaje'] = "Se ha guardado correctamente la Factura con codigo: ". $factura[0];
              $resultado['tipo'] = "success";
              $resultado['Id'] = $factura[1];
          }elseif($homologo[0] != false){
              
              if($homologo[0] != false){
                  $oItem = new complex("Dispensacion","Id_Dispensacion",$encabezadoHomologo['Id_Dispensacion']);
                  $dispensacion = $oItem->getData();
                  $oItem->Id_Factura = $homologo[1];
                  $oItem->Fecha_Facturado = date('Y-m-d H:i:s');
                  $oItem->Estado_Facturacion = "Facturada";
                  $oItem->Facturador_Asignado = 1095815196;
                  $oItem->Identificacion_Facturador = 1095815196;
                 $oItem->save();
                  unset($oItem);
              
                  $resultado['titulo'] = "Creacion exitosa";
                  $resultado['mensaje'] = "Se ha guardado correctamente la Homologación con codigo: ".$homologo[0];
                  $resultado['tipo'] = "success";
                  $resultado['Id'] = $homologo[1];
              }else{
                  $resultado['titulo'] = "Creacion no exitosa";
                  $resultado['mensaje'] = "ha ocurrido un error guardando la informacion, por favor verifique";
                  $resultado['tipo'] = "error";
              }
          } 
        }else{
            $dis_malas.=ObtenerCodigo($id).',';
        }
     
    }else{
        $dis_malas.=ObtenerCodigo($id).',';
    }
    
  

    }

    echo "Termino";
    echo 'Dispensaciones facturadas '.$dis_facturadas.'<br>';
    echo 'DIspensacion no facturadas '.$dis_malas;
        
   

    
   

   function GetDispensacion($id){
       global $queryObj;
        $query="SELECT 
        D.Codigo as Codigo, Dep.Nombre, P.EPS, P.Nit, DC.Id_Cliente, Dep.Id_Departamento , CONCAT(P.Id_Paciente , ' - ', P.Primer_Nombre, ' ', P.Primer_Apellido,  ' - Regimen ' , R.Nombre ) as Paciente, 'Evento' AS Tipo_Dispensacion, P.Id_Regimen, D.Cuota
        FROM `Dispensacion` D       
        INNER JOIN Paciente P 
            ON P.Id_Paciente = D.Numero_Documento
        INNER JOIN (SELECT Id_Punto_Dispensacion, Departamento FROM Punto_Dispensacion) PT
            ON PT.Id_Punto_Dispensacion = D.Id_Punto_Dispensacion 
        INNER JOIN Departamento Dep 
            ON Dep.Id_Departamento = P.Id_Departamento 
        INNER JOIN Departamento_Cliente DC 
            ON PT.Departamento = DC.Id_Departamento
        INNER JOIN Regimen R
        ON P.Id_Regimen = R.Id_Regimen
        WHERE Id_Dispensacion = ".$id ;

    
        $queryObj->SetQuery($query);
        $dis = $queryObj->ExecuteQuery('simple');
        return $dis;

    }
    
    function getFactura($dis){
        global $queryObj;
        if ($dis["Tipo_Dispensacion"] == "Evento" || $dis["Tipo_Dispensacion"] == "Cohortes" || ($dis["Tipo_Dispensacion"] == "NoPos" && $dis["Id_Regimen"] == 1)) {
            // busco cliente
            $query1 = 'SELECT Id_Cliente as Id_Cliente, Nombre as ClienteFactura, Condicion_Pago  FROM Cliente WHERE Id_Cliente ='.$dis["Nit"];
          
          } else {
            // busco cliente           
            $query1 = 'SELECT Id_Cliente as Id_Cliente, Nombre as ClienteFactura, Condicion_Pago  FROM Cliente WHERE Id_Cliente ='.$dis["Id_Cliente"];
            
          }

        $queryObj->SetQuery($query1);
        $factura = $queryObj->ExecuteQuery('simple');
        return $factura;
    }
   
    function GetHomologo($nit){
        global $queryObj;
        $query2 = 'SELECT Id_Cliente, Nombre as ClienteHomologo , Condicion_Pago 
         FROM  Cliente WHERE Id_Cliente ='.$nit;

        $queryObj->SetQuery($query2);
        $homologo = $queryObj->ExecuteQuery('simple');
        return $homologo;
    }

    function getProductos($factura,$dis,$id){
        global  $queryObj, $band_homologo, $productosHom; 
      /*   var_dump($dis);
        exit; */
        if ($dis["Tipo_Dispensacion"] == "Evento" ) {
            //busco los productos 
          $query3 = 'SELECT  
          CONCAT_WS(" ", p.Nombre_Comercial, p.Presentacion, p.Concentracion, " (",p.Principio_Activo ,") ", p.Cantidad, p.Unidad_Medida ) as Nombre ,
          i.Costo as CostoUnitario,
          i.Lote as Lote,
          i.Id_Inventario as Id_Inventario,
          p.Codigo_CUM as Cum,
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
          IF(p.Gravado = "Si" AND '.$factura['Id_Cliente'].' != 830074184, 0.19, 0) as Impuesto,
          "0" as Subtotal,
          (
          CASE
            WHEN PRG.Codigo_Cum IS NOT NULL THEN PRG.Precio
            WHEN PE.Codigo_Cum IS NOT NULL THEN PE.Precio
            ELSE 0
          END
          ) AS Precio_Venta_Factura,
          IF(PRG.Codigo_Cum IS NOT NULL, "Si", "No") AS Regulado,
          ( CASE
          WHEN PRG.Codigo_Cum IS NOT NULL THEN PRG.Precio*PD.Cantidad_Formulada
          WHEN PE.Codigo_Cum IS NOT NULL THEN PE.Precio*PD.Cantidad_Formulada
          ELSE 0
        END) as Subtotal, ( CASE
          WHEN PRG.Codigo_Cum IS NOT NULL THEN PRG.Precio
          WHEN PE.Codigo_Cum IS NOT NULL THEN PE.Precio
          ELSE 0
        END) as Precio,
          0 as Iva,  
          0 as Total_Descuento,
          IF(PE.Id_Producto_Evento IS NULL, 1, 0) AS Registrar
          FROM Producto_Dispensacion as PD 
          LEFT JOIN Precio_Regulado PRG
          ON PD.Cum = PRG.Codigo_Cum
          LEFT JOIN Producto_Evento PE
          ON PD.Cum = PE.Codigo_Cum AND PE.Nit_EPS = '.$dis['Nit'].'
          INNER JOIN Producto p
          on p.Id_Producto=PD.Id_Producto
          INNER JOIN Inventario i
          ON i.Id_Inventario = PD.Id_Inventario
          WHERE PD.Id_Dispensacion =  '.$id ;
          
          }elseif($dis["Tipo_Dispensacion"] == "Cohortes"){
            $query3 = 'SELECT  
            CONCAT_WS(" ", p.Nombre_Comercial, p.Presentacion, p.Concentracion, " (",p.Principio_Activo ,") ", p.Cantidad, p.Unidad_Medida ) as Nombre ,
            0 as CostoUnitario,
            PD.Lote as Lote,
            i.Id_Inventario as Id_Inventario,
            p.Codigo_CUM as Cum,
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
            IF(p.Gravado = "Si" AND '.$factura['Id_Cliente'].' != 830074184, 0.19, 0) as Impuesto,
            "0" as Subtotal,
            (
            CASE
              WHEN PRG.Codigo_Cum IS NOT NULL THEN PRG.Precio
              WHEN PE.Codigo_Cum IS NOT NULL THEN PE.Precio
              ELSE 0
            END
            ) AS Precio_Venta_Factura,
            IF(PRG.Codigo_Cum IS NOT NULL, "Si", "No") AS Regulado,
            ( CASE
            WHEN PRG.Codigo_Cum IS NOT NULL THEN PRG.Precio*PD.Cantidad_Formulada
            WHEN PE.Codigo_Cum IS NOT NULL THEN PE.Precio*PD.Cantidad_Formulada
            ELSE 0
          END) as Subtotal, ( CASE
            WHEN PRG.Codigo_Cum IS NOT NULL THEN PRG.Precio
            WHEN PE.Codigo_Cum IS NOT NULL THEN PE.Precio
            ELSE 0
          END) as Precio,
            0 as Iva,
            0 as Total_Descuento,
            IF(PE.Id_Producto IS NULL, 1, 0) AS Registrar
            FROM Producto_Dispensacion as PD 
            LEFT JOIN Precio_Regulado PRG
            ON PD.Cum = PRG.Codigo_Cum
            LEFT JOIN Producto_Cohorte PE
            ON PD.Id_Producto = PE.Id_Producto AND PE.Nit_EPS = '.$dis['Nit'].'
            INNER JOIN Producto p
            on p.Id_Producto=PD.Id_Producto
            LEFT JOIN Inventario i
            ON i.Id_Inventario = PD.Id_Inventario
            WHERE PD.Id_Dispensacion =  '.$id ;



          } elseif (($dis["Tipo_Dispensacion"] == "NoPos" && $dis["Id_Regimen"] == 1)) {
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
          IF(p.Gravado = "Si" AND '.$factura['Id_Cliente'].' != 830074184, 0.19, 0) as Impuesto,
          "0" as Subtotal,
          #IFNULL(PNP.Precio,0) as Precio_Venta_Factura,
          (
          CASE
            WHEN PRG.Codigo_Cum IS NOT NULL THEN PRG.Precio
            WHEN PNP.Cum IS NOT NULL THEN PNP.Precio
            ELSE 0
          END
          ) AS Precio_Venta_Factura,
          IF(PRG.Codigo_Cum IS NOT NULL, "Si", "No") AS Regulado,
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
          LEFT JOIN (SELECT PNP.* FROM Producto_NoPos PNP INNER JOIN Departamento_Lista_Nopos DLN ON DLN.Id_Lista_Producto_Nopos = PNP.Id_Lista_Producto_Nopos WHERE DLN.Id_Departamento = '.$dis["Id_Departamento"].') PNP
          ON PD.Cum = PNP.Cum
          WHERE PD.Id_Dispensacion =  '.$id ;
          } else {
            $band_homologo = true;
          //busco los productos 
          $query3 = 'SELECT  
          CONCAT_WS(" ", p.Nombre_Comercial, p.Presentacion, p.Concentracion, " (",p.Principio_Activo ,") ", p.Cantidad, p.Unidad_Medida ) as Nombre ,
          IFNULL(i.Costo,0) as CostoUnitario,
          PD.Lote as Lote,
          PD.Id_Inventario as Id_Inventario,
          PD.Cum as Cum,
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
          IF(p.Gravado = "Si" AND '.$factura['Id_Cliente'].' != 830074184, 0.19, 0) as Impuesto,
          "0" as Subtotal,
          (
          CASE
            WHEN PRG.Codigo_Cum IS NOT NULL THEN PRG.Precio
            WHEN PNP.Cum IS NOT NULL THEN PNP.Precio
            ELSE 0
          END
          ) AS Precio_Venta_Factura,
          IF(PRG.Codigo_Cum IS NOT NULL, "Si", "No") AS Regulado,
          0 as Precio,
          0 as Iva,
          0 as Total_Descuento,
          PNP.Cum_Homologo,
          IF(PNP.Cum_Homologo IS NOT NULL,(SELECT Id_Producto FROM Producto WHERE Codigo_Cum = PNP.Cum_Homologo LIMIT 1),"") AS Id_Producto_Hom,
          IF(PRG.Codigo_Cum IS NOT NULL,PRG.Precio,PNP.Precio_Homologo) AS Precio_Homologo,
          PNP.Detalle_Homologo,
          PNP.Id_Producto_NoPos,
          IF(PNP.Id_Producto_NoPos IS NULL, 1, 0) AS Registrar
          FROM Producto_Dispensacion as PD 
          INNER JOIN Producto p
          on p.Id_Producto=PD.Id_Producto
          LEFT JOIN Inventario i
          ON i.Id_Inventario = PD.Id_Inventario
          LEFT JOIN Precio_Regulado PRG
          ON PD.Cum = PRG.Codigo_Cum
          LEFT JOIN (SELECT PNP.* FROM Producto_NoPos PNP INNER JOIN Departamento_Lista_Nopos DLN ON DLN.Id_Lista_Producto_Nopos = PNP.Id_Lista_Producto_Nopos WHERE DLN.Id_Departamento = '.$dis["Id_Departamento"].') PNP
          ON PD.Cum = PNP.Cum
          WHERE PD.Id_Dispensacion =  '.$id ;
          }

         
  
        $queryObj->SetQuery($query3);
        $productos = $queryObj->ExecuteQuery('multiple');
        $productosHom=$productos;
        $i=-1;
        foreach($productos as $lista){$i++;
        $productos[$i]['Impuesto'] = (FLOAT) $lista['Impuesto'];
        $productos[$i]['Impuesto'] = (FLOAT) $lista['Impuesto'];

        }
        return $productos;
          
    }


    function guardarFactura($datos, $productos, $tipo,$mod){
      global $MY_FILE;
      global $datos_dis;
      global $id_factura_asociada;
     global $contabilizar;
  
      switch($tipo){
          case "Factura":{
  
              $oItem = new complex('Resolucion','Id_Resolucion',3); // Resolucion 3 para Facturas Ventas NoPos
              $nc = $oItem->getData();
  
              $oItem->Consecutivo=$oItem->Consecutivo+1;
              $oItem->save();
              $num_cotizacion=$nc["Consecutivo"];
              unset($oItem);
                  
              $cod = $nc["Codigo"].$nc["Consecutivo"];
              
              $datos['Codigo']=$cod;
  
              break;
          }
          case "Homologo":{

              $configuracion = new Configuracion();
              $oItem = new complex('Resolucion','Id_Resolucion',3); // Resolucion 3 para Facturas Ventas NoPos
              $nc = $oItem->getData();
  
              $oItem->Consecutivo=$oItem->Consecutivo+1;
              $oItem->save();
              $num_cotizacion=$nc["Consecutivo"];
              unset($oItem);
                  
              $cod = $nc["Codigo"].$nc["Consecutivo"];
              
              $datos['Codigo']=$cod;
              break;
          }
      }
      
    
     $oItem = new complex($mod,"Id_".$mod);
  
      
      foreach($datos as $index=>$value) {
          $oItem->$index=$value;
      }
      if ($tipo == 'Homologo') {
          $id = (INT) $id_factura_asociada;
          $oItem->Id_Factura_Asociada = number_format($id,0,"","");
      }
      $oItem->save();
      $id_factura = $oItem->getId();
      if ($tipo == 'Factura') { 
          $id_factura_asociada = $id_factura;
      }
  
      
      
      $resultado = array();
      unset($oItem);
  
      /* AQUI GENERA QR */
      $qr = generarqr('factura',$id_factura,'/IMAGENES/QR/');;
      $oItem = new complex("Factura","Id_Factura",$id_factura);
      $oItem->Codigo_Qr=$qr;
      $oItem->save();
      unset($oItem);
      /* HASTA AQUI GENERA QR */  

  
      foreach($productos as $producto){
          $oItem = new complex('Producto_'.$mod,"Id_Producto_".$mod);
          $producto["Id_".$mod]=$id_factura;
          $subtotal = number_format((INT) $producto['Subtotal'],2,".","");
          $producto['Subtotal'] = $subtotal;
          $producto['Fecha_Vencimiento']=$producto['Fecha_Vencimiento']!='' ? $producto['Fecha_Vencimiento'] : GetFechaVencimiento($producto['Lote'],$producto['Id_Producto']);

          $producto['Id_Inventario']=$producto['Id_Inventario']!='' ? $producto['Id_Inventario'] : '0' ;
          foreach($producto as $index=>$value) {
              $oItem->$index=$value;
          }
          $impuesto = $producto['Impuesto'] != 0 ? (FLOAT) $producto['Impuesto'] * 100 : 0;
          $oItem->Impuesto = number_format((INT) $impuesto, 0, "","");
          $oItem->Precio = number_format($producto['Precio'],2,".","");
          $oItem->Descuento = number_format($producto['Descuento'],2,".","");
          $oItem->save();
          unset($oItem);
  
          if ($producto['Registrar'] == 1 && $datos_dis['Tipo_Dispensacion'] == 'NoPos') { // Actualizar tabla de producto No pos
              if ($tipo == 'Factura') {
                  $q = "SELECT DLN.Id_Lista_Producto_Nopos, PNP.Id_Producto_NoPos FROM Departamento_Lista_Nopos DLN LEFT JOIN Producto_NoPos PNP ON DLN.Id_Lista_Producto_Nopos = PNP.Id_Lista_Producto_Nopos WHERE DLN.Id_Departamento = $datos_dis[Id_Departamento] AND PNP.Cum= '$producto[Cum]'";
  
                  $oCon = new consulta();
                  $oCon->setQuery($q);
                  $res = $oCon->getData();
                  unset($oCon);
  
                  if (!$res) {
  
                      $q = "SELECT DLN.Id_Lista_Producto_Nopos FROM Departamento_Lista_Nopos DLN WHERE DLN.Id_Departamento = $datos_dis[Id_Departamento]"; // Obtener el ID de Lista NoPos
  
                      $oCon = new consulta();
                      $oCon->setQuery($q);
                      $res = $oCon->getData();
                      unset($oCon);
                          
                      $oItem = new complex('Producto_NoPos','Id_Producto_NoPos');
                      $oItem->Cum = $producto['Cum'];
                      $oItem->Precio = $producto['Precio'];
                      $oItem->Id_Lista_Producto_Nopos = $res['Id_Lista_Producto_Nopos'];
                      $oItem->save();
                      unset($oItem);
                  }
              } elseif ($tipo == 'Homologo') {
                  $q = "SELECT DLN.Id_Lista_Producto_Nopos, PNP.Id_Producto_NoPos FROM Departamento_Lista_Nopos DLN INNER JOIN Producto_NoPos PNP ON DLN.Id_Lista_Producto_Nopos = PNP.Id_Lista_Producto_Nopos WHERE DLN.Id_Departamento = $datos_dis[Id_Departamento] AND PNP.Cum= '$producto[Cum]'";
  
                  $oCon = new consulta();
                  $oCon->setQuery($q);
                  $res = $oCon->getData();
                  unset($oCon);
  
                  if ($res) {
                      $oItem = new complex('Producto_NoPos','Id_Producto_NoPos', $res['Id_Producto_NoPos']);
                      $oItem->Cum_Homologo = $producto['Cum_Homologo'];
                      $oItem->Precio_Homologo = $producto['Precio'];
                      $oItem->Detalle_Homologo = $producto['Detalle_Homologo'];
                      $oItem->save();
                      unset($oItem);
                  }
              }
          } elseif ($producto['Registrar'] == 1 && $datos_dis['Tipo_Dispensacion'] == 'Evento') {
              $q = "SELECT PE.Id_Producto_Evento FROM Producto_Evento PE WHERE PE.Nit_EPS = $datos_dis[Nit] AND PE.Codigo_Cum= '$producto[Cum]'";
  
              $oCon = new consulta(); 
              $oCon->setQuery($q);
              $res = $oCon->getData();
              unset($oCon);
  
              if (!$res) {
                  $oItem = new complex('Producto_Evento','Id_Producto_Evento');
                  $oItem->Codigo_Cum = $producto['Cum'];
                  $oItem->Precio = $producto['Precio'];
                  $oItem->Nit_EPS = $datos_dis['Nit'];
                  $oItem->save();
                  unset($oItem);
              }  
          } 
      }
      
      ValidarCouta($id_factura);
      $datos_movimiento_contable['Id_Registro'] = $id_factura;
      $datos_movimiento_contable['Nit'] = $datos['Id_Cliente'];
      $datos_movimiento_contable['Id_Regimen'] = $datos_dis['Id_Regimen'];
      //$contabilizar->CrearMovimientoContable('Factura', $datos_movimiento_contable);
      
      if($id_factura != "" || $id_factura != NULL ){
          return [$cod, $id_factura];
      }else{
          return false;
      }
      
  }
 function ObtenerFechaPago($condicion){
   if($condicion=='1'){
     $fecha=date('Y-m-d');
   }else{
     $fecha=strtotime('+'.$condicion.' days',time() );
   }

   return date('Y-m-d',$fecha);
 }

 function ObtenerCodigo($id){
    $q = "SELECT Codigo FROM Dispensacion WHERE Id_Dispensacion=".$id;
  
    $oCon = new consulta();
    $oCon->setQuery($q);
    $res = $oCon->getData();
    unset($oCon);

    return $res['Codigo'];
 }

 function ValidarCouta($id_factura){
    $q = "SELECT Cuota FROM Factura WHERE Id_Factura=".$id_factura;
    
    $oCon = new consulta();
    $oCon->setQuery($q);
    $couta = $oCon->getData();
    unset($oCon);

    $query = "SELECT SUM((Cantidad*Precio)+((Cantidad*Precio)*(Impuesto/100))) as Precio FROM Producto_Factura WHERE Id_Factura=".$id_factura;
    
    $oCon = new consulta();
    $oCon->setQuery($query);
    $precio_factura = $oCon->getData();
    unset($oCon);

    if($couta['Couta']>=$precio_factura['Precio']){
      $oItem = new complex("Factura","Id_Factura",$id_factura);
      $oItem->Cuota='0';
      $oItem->save();
      unset($oItem);
    }


 }

 function GetFechaVencimiento($lote,$id_producto){  

  $query = "SELECT Fecha_Vencimiento FROM Inventario WHERE Id_Bodega!=0 AND  Lote LIKE '".$lote."' AND Id_Producto=".$id_producto;
    
  $oCon = new consulta();
  $oCon->setQuery($query);
  $fecha = $oCon->getData();
  unset($oCon);
  return $fecha['Fecha_Vencimiento'];
 }

?>