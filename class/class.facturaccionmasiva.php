<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/config/start.inc.php');
include_once('class.querybasedatos.php');
include_once('class.contabilizar.php');
include_once('class.qr.php');
// include_once('../../class/class.portal_clientes.php'); //DESCOMENTAR ESTA LINEA PARA GUARDAR EN EL PORTAL CLIENTES
    
class Facturacion_Masiva{

    private $dispensaciones;
    private $queryObj;
    private $datos_dis;
    private $contabilizar;
    private $funcionario;
    private $tipo;
// private $portalClientes; //DESCOMENTAR ESTA LINEA PARA GUARDAR EN EL PORTAL CLIENTES
      
      function __construct(){
        $this->queryObj = new QueryBaseDatos();
        $this->contabilizar=  new Contabilizar();
        //$this->portalClientes = new PortalCliente($this->queryObj);    
      } 

      function __destruct(){
        $this->queryObj = null;
        unset($queryObj);	
      }


    public function Facturacion($dispensaciones,$funcionario,$tipo){
        $this->dispensaciones=$dispensaciones;
        $this->funcionario=$funcionario;
        $this->tipo=$tipo;
       
        $dispensaciones=$this->GetDispensaciones();

    

        foreach ($dispensaciones as $value) {
            $id=$value['Id_Dispensacion'];
            $this->datos_dis = $this->GetDispensacion($id);

            if($this->datos_dis){
              if($this->datos_dis['Nit']!=''){
                $factura=$this->getFactura($this->datos_dis);
               
                if($factura){            
                    $productos=$this->getProductos($factura,$this->datos_dis,$id);

                
                    $encabezadoFactura['Fecha_Documento']=date("Y-m-d H:i:s");
                    $encabezadoFactura['Id_Cliente']=$factura['Id_Cliente'];
                    $encabezadoFactura['Id_Funcionario']=$this->funcionario;
                    $encabezadoFactura['Estado_Factura']="Sin Cancelar";
                    $encabezadoFactura['Id_Dispensacion']= $id;
                    $encabezadoFactura['Condicion_Pago']=$factura['Condicion_Pago'];
                    $encabezadoFactura['Cuota']=$this->datos_dis['Cuota'];
                    $encabezadoFactura['Fecha_Pago']=$this->ObtenerFechaPago($factura['Condicion_Pago']);
                    $encabezadoFactura['Tipo']='Factura';
                   

                    $factura = $this->guardarFactura($encabezadoFactura, $productos, "Factura",'Factura');

                    if($factura!=''){
                        $oItem = new complex("Dispensacion","Id_Dispensacion",$encabezadoFactura['Id_Dispensacion']);
                        $oItem->Id_Factura = $factura;
                        $oItem->Fecha_Facturado = date('Y-m-d H:i:s');
                        $oItem->Estado_Facturacion = "Facturada";
                        $oItem->Facturador_Asignado = $this->funcionario;
                        $oItem->Identificacion_Facturador = $this->funcionario;
                        $oItem->save();
                        unset($oItem);


                      // $this->GuardarDispensacionPortalClientes($encabezadoFactura['Id_Dispensacion']); //DESCOMENTAR ESTA LINEA PARA GUARDAR EN EL PORTAL CLIENTES
                    }
                }
              }
               
             
            }
        }


      
    }

private function GetDispensaciones(){

    $query="SELECT  Id_Dispensacion FROM Dispensacion WHERE Id_Dispensacion IN (".$this->dispensaciones.")";
    $this->queryObj->SetQuery($query);
    $dis=$this->queryObj->ExecuteQuery('Multiple');

    return $dis;

}

 private  function GetDispensacion($id){
    global $queryObj;
    $tipo=$this->tipo;
     $query="SELECT 
     D.Codigo as Codigo, Dep.Nombre, P.EPS, P.Nit, DC.Id_Cliente, Dep.Id_Departamento , CONCAT(P.Id_Paciente , ' - ', P.Primer_Nombre, ' ', P.Primer_Apellido,  ' - Regimen ' , R.Nombre ) as Paciente, '$tipo' AS Tipo_Dispensacion, P.Id_Regimen, IFNULL(D.Cuota, 0 ) as Cuota, D.Id_Punto_Dispensacion
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
     WHERE Id_Dispensacion = ".$id ." AND D.Estado_Facturacion='Sin Facturar'";

 
     $queryObj->SetQuery($query);
     $dis = $queryObj->ExecuteQuery('simple');
     return $dis;

 }
 
 private function getFactura($dis){
     global $queryObj;
     if ($dis["Tipo_Dispensacion"] == "Evento" || $dis["Tipo_Dispensacion"] == "Cohortes" ) {
         // busco cliente
         $query1 = 'SELECT Id_Cliente as Id_Cliente, Nombre as ClienteFactura, Condicion_Pago  FROM Cliente WHERE Id_Cliente ='.$dis["Nit"];
       
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

 private function getProductos($factura,$dis,$id){
    
   
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



       }
      

     $this->queryObj->SetQuery($query3);
     $productos = $this->queryObj->ExecuteQuery('multiple');
     
     $i=-1;
     foreach($productos as $lista){$i++;
     $productos[$i]['Impuesto'] = (FLOAT) $lista['Impuesto'];
     $productos[$i]['Impuesto'] = (FLOAT) $lista['Impuesto'];

     }
     return $productos;
       
 }


 private function guardarFactura($datos, $productos, $tipo,$mod){
  

    $query="SELECT * FROm Resolucion WHERE Modulo='NoPos' AND Consecutivo <=Numero_Final";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $resolucion = $oCon->getData();
    unset($oCon);

    $id_factura='';

    if($resolucion['Id_Resolucion']){
        $oItem = new complex('Resolucion','Id_Resolucion',$resolucion['Id_Resolucion']); // Resolucion 3 para Facturas Ventas NoPos
        $nc = $oItem->getData();
        $oItem->Consecutivo=$oItem->Consecutivo+1;
        $oItem->save();
        unset($oItem);                
        $cod = $nc["Codigo"].$nc["Consecutivo"];            
        $datos['Codigo']=$cod;
        $datos['Id_Resolucion']=$resolucion['Id_Resolucion'];

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
            $producto['Fecha_Vencimiento']=$producto['Fecha_Vencimiento']!='' ? $producto['Fecha_Vencimiento'] : $this->GetFechaVencimiento($producto['Lote'],$producto['Id_Producto']);
     
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
     
         
        }
        
        $this->ValidarCouta($id_factura);
        $datos_movimiento_contable['Id_Registro'] = $id_factura;
        $datos_movimiento_contable['Nit'] = $datos['Id_Cliente'];
        $datos_movimiento_contable['Id_Regimen'] = $this->datos_dis['Id_Regimen'];
        $datos_movimiento_contable['Id_Punto_Dispensacion'] = $this->datos_dis['Id_Punto_Dispensacion'];
        $this->contabilizar->CrearMovimientoContable('Factura', $datos_movimiento_contable);
        
        

    }

    return $id_factura;
   
 

   
}
private  function ObtenerFechaPago($condicion){

    if($condicion=='1'){
      $fecha=date('Y-m-d');
      return $fecha;
    }else{
      $fecha=strtotime('+'.$condicion.' days',time() );
      return date('Y-m-d',$fecha);
    }
 
  
  }

  private function ValidarCouta($id_factura){
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

    if($couta['Cuota']>=$precio_factura['Precio']){
      $oItem = new complex("Factura","Id_Factura",$id_factura);
      $oItem->Cuota='0';
      $oItem->save();
      unset($oItem);
    }


 }


 private function GetFechaVencimiento($lote,$id_producto){  

    $query = "SELECT Fecha_Vencimiento FROM Inventario WHERE Id_Bodega!=0 AND  Lote LIKE '".$lote."' AND Id_Producto=".$id_producto;
      
    $oCon = new consulta();
    $oCon->setQuery($query);
    $fecha = $oCon->getData();
    unset($oCon);
    return $fecha['Fecha_Vencimiento'];
   }

  //DESCOMENTAR ESTE METODO PARA GUARDAR EN EL PORTAL CLIENTES
  // private function GuardarDispensacionPortalClientes($idDis){
  //   global $portalClientes;

  //   $response = $portalClientes->ActualizarDispensacion($idDis);

  // }

}



?>