<?php
  header('Access-Control-Allow-Origin: *');
  header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
  date_default_timezone_set('America/Bogota');
  include_once('../class/class.http_response.php');
  include_once('../class/class.querybasedatos.php');
  require_once('../class/class.configuracion.php');
  //include_once('../class/class.contabilizar.php');
  require_once('../class/class.qr.php'); 
  require_once('../config/start.inc.php');

   //$contabilizar = new Contabilizar();
    $queryObj = new QueryBaseDatos();
    $http_response = new HttpResponse();
    $response = array();
     $query="SELECT D.Id_Dispensacion 
      FROM Dispensacion D
    WHERE
    D.Codigo IN ('DIS1352713',
'DIS1352716',
'DIS1352743',
'DIS1352751',
'DIS1352720',
'DIS1352809',
'DIS1352761',
'DIS1352726',
'DIS1352734',
'DIS1352770',
'DIS1352783',
'DIS1352788',
'DIS1352789',
'DIS1352812',
'DIS1352735',
'DIS1352721',
'DIS1352722',
'DIS1352723',
'DIS1352725',
'DIS1352731',
'DIS1352733',
'DIS1352740',
'DIS1352746',
'DIS1352747',
'DIS1352748',
'DIS1352749',
'DIS1352744',
'DIS1352758',
'DIS1352759',
'DIS1352762',
'DIS1352765',
'DIS1352766',
'DIS1352768',
'DIS1352779',
'DIS1352780',
'DIS1352781',
'DIS1352782',
'DIS1352764',
'DIS1352792',
'DIS1352793',
'DIS1352767',
'DIS1352794',
'DIS1352796',
'DIS1352773',
'DIS1352774',
'DIS1352776',
'DIS1352797',
'DIS1352802',
'DIS1352763',
'DIS1352714',
'DIS1352784',
'DIS1352715',
'DIS1352811',
'DIS1352718',
'DIS1352719',
'DIS1352790',
'DIS1352717',
'DIS1352732',
'DIS1352795',
'DIS1352724',
'DIS1352741',
'DIS1352750',
'DIS1352752',
'DIS1352757',
'DIS1352813',
'DIS1352799',
'DIS1352814',
'DIS1352745',
'DIS1352803',
'DIS1352806',
'DIS1352807',
'DIS1352800',
'DIS1352778',
'DIS1352786') AND D.Estado_Facturacion!='Facturada' AND D.Estado_Dispensacion!='Anulada'";                          
    $queryObj->SetQuery($query);  
    $productos_facturacion = $queryObj->ExecuteQuery('multiple');  

    $dis_malas='';
    $dis_facturadas='';

    foreach ($productos_facturacion as  $value) {
      $id=$value['Id_Dispensacion'];
     
      $datos_dis = GetDispensacion($id);
  
    if($datos_dis){
        $factura=getFactura($datos_dis);

       
        if($factura){
           $dis_facturadas.=ObtenerCodigo($id).',';
           
            $productos=getProductos($factura,$datos_dis,$id);
               
            $encabezadoFactura['Fecha_Documento']=date("Y-m-d H:i:s");
            $encabezadoFactura['Id_Cliente']=$factura['Id_Cliente'];
            $encabezadoFactura['Id_Funcionario']=12345;
            $encabezadoFactura['Estado_Factura']="Sin Cancelar";
            $encabezadoFactura['Id_Dispensacion']= $id;
            $encabezadoFactura['Condicion_Pago']=$factura['Condicion_Pago'];
            $encabezadoFactura['Cuota']=$datos_dis['Cuota'];
            $encabezadoFactura['Fecha_Pago']=ObtenerFechaPago($factura['Condicion_Pago']);
            $encabezadoFactura['Tipo']='Factura';
            $id_factura_asociada = '';
      
            $factura = guardarFactura($encabezadoFactura, $productos, "Factura",'Factura');
  
            if($factura[0] != false){
              
              $oItem = new complex("Dispensacion","Id_Dispensacion",$encabezadoFactura['Id_Dispensacion']);
              $dispensacion = $oItem->getData();
              $oItem->Id_Factura = $factura[1];
              $oItem->Fecha_Facturado = date('Y-m-d H:i:s');
              $oItem->Estado_Facturacion = "Facturada";
              $oItem->Facturador_Asignado = 12345;
              $oItem->Identificacion_Facturador = 12345;
              $oItem->save();
              unset($oItem);
              
              $resultado['titulo'] = "Creacion exitosa";
              $resultado['mensaje'] = "Se ha guardado correctamente la Factura con codigo: ". $factura[0];
              $resultado['tipo'] = "success";
              $resultado['Id'] = $factura[1];
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
        D.Codigo as Codigo, 'Santander' AS Nombre, P.EPS, 900226715 AS Nit, 900226715 AS Id_Cliente,  21 AS Id_Departamento , CONCAT(P.Id_Paciente , ' - ', P.Primer_Nombre, ' ', P.Primer_Apellido,  ' - Regimen ' , R.Nombre ) as Paciente, 'MIPRES' AS Tipo_Dispensacion, P.Id_Regimen, D.Cuota
        FROM `Dispensacion` D       
        INNER JOIN Paciente P 
            ON P.Id_Paciente = D.Numero_Documento
        INNER JOIN (SELECT Id_Punto_Dispensacion, Departamento FROM Punto_Dispensacion) PT
            ON PT.Id_Punto_Dispensacion = D.Id_Punto_Dispensacion 
        
        INNER JOIN Regimen R
        ON P.Id_Regimen = R.Id_Regimen
        WHERE Id_Dispensacion = ".$id ;

    
        $queryObj->SetQuery($query);
        $dis = $queryObj->ExecuteQuery('simple');
        return $dis;

    }
    
    function getFactura($dis){
        global $queryObj;
        
        $query1 = 'SELECT Id_Cliente as Id_Cliente, Nombre as ClienteFactura, Condicion_Pago  FROM Cliente WHERE Id_Cliente ='.$dis["Nit"];
          
        $queryObj->SetQuery($query1);
        $factura = $queryObj->ExecuteQuery('simple');
        return $factura;
    }
   


    function getProductos($factura,$dis,$id){
        global  $queryObj;

          $query3 = 'SELECT  
          CONCAT_WS(" ", p.Nombre_Comercial, p.Presentacion, p.Concentracion, " (",p.Principio_Activo ,") ", p.Cantidad, p.Unidad_Medida ) as Nombre ,
          i.Costo as CostoUnitario,
          i.Lote as Lote,
          i.Id_Inventario_Nuevo as Id_Inventario_Nuevo,
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
          IF(p.Gravado = "Si", 0.19, 0) as Impuesto,
          "0" as Subtotal,
          IFNULL(EN.Precio,0) AS Precio_Venta_Factura,
          "No" AS Regulado,
          IFNULL(EN.Precio*PD.Cantidad_Formulada,0) as Subtotal, 
          IFNULL(EN.Precio,0) as Precio,
          0 as Iva,  
          0 as Total_Descuento,
          0 AS Registrar
          FROM Producto_Dispensacion as PD 
          INNER JOIN Producto p
          on p.Id_Producto=PD.Id_Producto
          INNER JOIN Inventario_Nuevo i
          ON i.Id_Inventario_Nuevo = PD.Id_Inventario_Nuevo
          LEFT JOIN A_Entrega_Nutriciones_2025 EN ON EN.id_producto = p.Id_Producto AND EN.id_dispensacion = '.$id.'
          WHERE PD.Id_Dispensacion =  '.$id ;


/*WHEN p.Id_Producto IN (53173,54433,54435,54436) THEN 1030*PD.Cantidad_Formulada
  WHEN p.Id_Producto IN (58121,58122,58123,58124,58125,58126,58127,58128) THEN 2430*PD.Cantidad_Formulada
*/
        $queryObj->SetQuery($query3);
        $productos = $queryObj->ExecuteQuery('multiple');
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
  
   
      $oItem = new complex('Resolucion','Id_Resolucion',42); // Resolucion 42 para Facturas Ventas NoPos Marzo de 2025 hasat Marzo de 2026
      $nc = $oItem->getData();

      $oItem->Consecutivo=$oItem->Consecutivo+1;
      $oItem->save();
      $num_cotizacion=$nc["Consecutivo"];
      unset($oItem);
          
      $cod = $nc["Codigo"].$nc["Consecutivo"];
      $datos['Codigo']=$cod;
      $datos['Id_Resolucion']=42;
  
     $oItem = new complex($mod,"Id_".$mod);
  
      foreach($datos as $index=>$value) {
          $oItem->$index=$value;
      }
      $oItem->save();
      $id_factura = $oItem->getId();

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

          $producto['Id_Inventario_Nuevo']=$producto['Id_Inventario_Nuevo']!='' ? $producto['Id_Inventario_Nuevo'] : '0' ;
          foreach($producto as $index=>$value) {
              $oItem->$index=$value;
          }
          $impuesto = $producto['Impuesto'] != 0 ? (FLOAT) $producto['Impuesto'] * 100 : 0;
          $oItem->Impuesto = number_format((INT) $impuesto, 0, "","");
          $oItem->Precio = number_format($producto['Precio'],2,".","");
          $oItem->Descuento = number_format($producto['Descuento'],2,".","");
          $oItem->save();
          unset($oItem);
  
     }
      
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


 function GetFechaVencimiento($lote,$id_producto){  

  $query = "SELECT Fecha_Vencimiento FROM Inventario_Nuevo WHERE Id_Bodega!=0 AND  Lote LIKE '".$lote."' AND Id_Producto=".$id_producto;
    
  $oCon = new consulta();
  $oCon->setQuery($query);
  $fecha = $oCon->getData();
  unset($oCon);
  return $fecha['Fecha_Vencimiento'];
 }

?>