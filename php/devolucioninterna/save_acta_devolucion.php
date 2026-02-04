<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.http_response.php');
require_once('../../class/class.configuracion.php');
require_once('../../class/class.qr.php');

$queryObj = new QueryBaseDatos();
$response = array();
$http_response = new HttpResponse();

$configuracion = new Configuracion();

$codigos_rem='';

date_default_timezone_set('America/Bogota');

$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );
$productos = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );
 

$modelo = (array) json_decode(utf8_decode($modelo));
$productos = (array) json_decode(utf8_decode($productos) , true);

$id_remision='';
$id_acta_recepcion='';

$datos_encabezado=GetDatosEncabezado($modelo);



SaveEncabezado($datos_encabezado);
SaveProductos($productos);
ActualizarEstadoDevolucionInterna($datos_encabezado['Id_Devolucion_Interna']);


$codigo_acta=GetCodigoActa($id_acta_recepcion);

$http_response->SetRespuesta(0, 'Guardado Correctamente', 'Se ha guardado correctamente la devolucion Interna. Genera la remision '.trim($codigos_rem,',').' y el Acta '.$codigo_acta);
$response = $http_response->GetRespuesta();

echo json_encode($response);
function ActualizarEstadoDevolucionInterna($id){
     global $queryObj;

     $query = 'UPDATE Devolucion_Interna SET Estado = "Recibida" WHERE Id_Devolucion_Interna = '.$id;
       $queryObj->SetQuery($query);
       $queryObj->QueryUpdate();
}

function GetDatosEncabezado($modelo){
     global $queryObj;
     $query = "SELECT D.*, 'Interna' as Tipo, $modelo[Identificacion_Funcionario] as Identificacion_Funcionario, CONCAT('Remision de la devolucion interna con codigo ',Codigo) as Observaciones, 'Punto_Dispensacion' as Tipo_Origen,'Bodega' as Tipo_Destino, 'Recibida' as Estado, 2 as Estado_Alistamiento, Id_Destino as Id_Bodega   FROM Devolucion_Interna D WHERE D.Id_Devolucion_Interna =$modelo[Id_Devolucion_Interna] ";
     $queryObj->SetQuery($query);
     $encabezado = $queryObj->ExecuteQuery('simple');
     return $encabezado;
}

function validarCodigo($cod){
     $query = "SELECT Id_Remision FROM Remision WHERE Codigo = '$cod'";
  
     $oCon = new consulta();
     $oCon->setQuery($query);
     $resultado = $oCon->getData();
     unset($oCon);
 
     return $resultado || false;
  }


  function SaveEncabezado($modelo){

     global $id_remision,$id_acta_recepcion;
     $rem=$modelo;
     $acta=$modelo;
     
     $rem['Fecha']=date("Y-m-d H:i:s");
     $rem['Id_Categoria']= '0';
     $oItem = new complex("Remision","Id_Remision");
     foreach($rem as $index=>$value) {
          if($value!=''){
                    $oItem->$index=$value;
          }
         
      }
      $oItem->Codigo=GetCodigo('Remision');
      $oItem->save();
      $id_remision = $oItem->getId();
      unset($oItem);

     $qr = generarqr('remision',$id_remision,'/IMAGENES/QR/');
     $oItem = new complex("Remision","Id_Remision",$id_remision);
     $oItem->Codigo_Qr=$qr;
     $oItem->save();
     unset($oItem);

     $acta['Fecha']=date("Y-m-d H:i:s");
     $acta['Tipo']='Bodega';
     $acta['Id_Remision']=$id_remision;
     $acta['Estado']='Aprobada';

     $oItem = new complex("Acta_Recepcion_Remision","Id_Acta_Recepcion_Remision");
     foreach($acta as $index=>$value) {      
               $oItem->$index=$value;   
      }
      $oItem->Codigo=GetCodigo('Acta_Recepcion_Remision');
      $oItem->save();
      $id_acta_recepcion = $oItem->getId();      
      unset($oItem);

      $qr = generarqr('actarecepcionremision',$id_acta_recepcion,'/IMAGENES/QR/');
     $oItem = new complex("Acta_Recepcion_Remision","Id_Acta_Recepcion_Remision",$id_acta_recepcion);
     $oItem->Codigo_Qr=$qr;
     $oItem->save();
     unset($oItem);


     

  }


  function GetCodigo($tipo){
       global $configuracion,$queryObj;
       $codigo=$configuracion->Consecutivo($tipo);
       sleep(2); // Esperar 2 segundo antes de hacer la validación.
       $query = "SELECT Id_$tipo FROM $tipo WHERE Codigo = '$codigo'";
       $queryObj->SetQuery($query);
       $rem = $queryObj->ExecuteQuery('simple');
       if($rem['Id_'.$tipo]){
          $codigo=$configuracion->Consecutivo($tipo);
       }
       return $codigo;
  }



  function SaveProductos($productos){
      global $id_acta_recepcion,$id_remision,$datos_encabezado;
   
       foreach ($productos as $producto) {                              
                    
                    $p=$producto;
                    $subtotal=$p['Cantidad']*$p['Precio'];
                    $p['Id_Remision']=$id_remision;
                    $oItem=new complex('Producto_Remision',"Id_Producto_Remision");
                    foreach($p as $index=>$value) {
                         $oItem->$index=$value;
                     }
                    $oItem->Cantidad=$p['Cantidad'];
                    $oItem->Precio=number_format($producto['Precio'],2,".","");
                    $oItem->Cantidad_Total=$p['Cantidad'];
                    $oItem->Nombre_Producto=$p['Nombre_Comercial'];
                    $oItem->Precio=number_format($producto['Precio'],2,".","");
                    $oItem->Subtotal=number_format($subtotal,2,".","");
                    $oItem->save();
                    $id_producto_remision = $oItem->getId();
                    unset($oItem);
                    
                    $p['Id_Producto_Remision']=$id_producto_remision;
                    $p['Id_Acta_Recepcion_Remision']=$id_acta_recepcion;
                    $p['Cumple']='Si';
                    $p['Revisado']='Si';

                    $oItem = new complex('Producto_Acta_Recepcion_Remision','Id_Producto_Acta_Recepcion_Remision');
                    foreach($p as $index=>$value) {
                         $oItem->$index=$value;
                     }
                     $oItem->save();
                     unset($oItem);

                     //descontar del inventario del punto 
                   // Agregar al inventario el nuevo producto
                    $oItem = new complex('Inventario','Id_Inventario',$p['Id_Inventario']);
                    $cantidad_actual=$oItem->Cantidad;
                    $cantidad_descontar=$p['Cantidad'];
                    $cant_final=$cantidad_actual-$cantidad_descontar;
                    if($cant_final<0){
                         $cant_final=0;
                    } 
                    $oItem->Cantidad=number_format($cant_final,0,"","");    
                    $oItem->save();
                    unset($oItem);


                     $query = 'SELECT I.*
                    FROM Inventario I
                    WHERE I.Id_Bodega='.$datos_encabezado['Id_Bodega'].' AND I.Id_Producto='.$p['Id_Producto'].' AND I.Lote="'.$p['Lote'].'"' ;

                    $oCon= new consulta();
                    $oCon->setQuery($query);
                    $inventario = $oCon->getData();
                    unset($oCon);


                    if($inventario){
                         $actual=number_format($p["Cantidad"],0,"","");
                         $suma=number_format($inventario["Cantidad"],0,"","");
                         $total=$suma+$actual;
                         // Agregar al inventario el nuevo producto
                         $oItem = new complex('Inventario','Id_Inventario',$inventario['Id_Inventario']);
                         $oItem->Identificacion_Funcionario = $datos_encabezado['Identificacion_Funcionario'];
                         $oItem->Id_Producto= $p['Id_Producto'];
                         $oItem->Costo = $p['Precio'];
                         $oItem->Codigo_CUM = $p['Codigo_Cum'];
                         $oItem->Cantidad = number_format($total,0,"","");
                         $oItem->Lote = $p['Lote'];
                         $oItem->Fecha_Carga =date("Y-m-d H:i:s");             
                         $oItem->save();
                         unset($oItem);
                    }else{
                         $oItem = new complex('Inventario','Id_Inventario');
                         $oItem->Identificacion_Funcionario = $datos_encabezado['Identificacion_Funcionario'];
                         $oItem->Id_Bodega= $datos_encabezado['Id_Bodega'];
                         $oItem->Id_Producto= $p['Id_Producto'];
                         $oItem->Costo = $p['Precio'];
                         $oItem->Cantidad = number_format($p['Cantidad'],0,"","");
                         $oItem->Codigo_CUM = $p['Codigo_Cum'];
                         $oItem->Lote = $p['Lote'];
                         $oItem->Fecha_Carga =date("Y-m-d H:i:s"); 
                         $oItem->Fecha_Vencimiento = $p['Fecha_Vencimiento'];            
                         $oItem->save();
                         unset($oItem);
                    }
        }
        GuardarActividadRemision($id_remision);
  }




 function  GuardarActividadRemision($id_remision){
     global $datos_encabezado;

     $codigo_rem=GetCodigoRem($id_remision);

     $oItem = new complex('Actividad_Remision',"Id_Actividad_Remision");
     $oItem->Id_Remision=$id_remision;
     $oItem->Identificacion_Funcionario=$datos_encabezado["Identificacion_Funcionario"];
     $oItem->Detalles="Se creo la remision con codigo ".$codigo_rem;
     $oItem->Fecha=date("Y-m-d H:i:s");
     $oItem->save();
     unset($oItem);

     $oItem = new complex('Actividad_Remision',"Id_Actividad_Remision");
     $oItem->Id_Remision=$id_remision;
     $oItem->Identificacion_Funcionario=$datos_encabezado["Identificacion_Funcionario"];
     $oItem->Detalles="Se hace el acta de recepcion de la ".$codigo_rem;
     $oItem->Fecha=date("Y-m-d H:i:s");
     $oItem->Estado = "Recibida";
     $oItem->save();
     unset($oItem);
  }



  function GetCodigoRem($id_remision){
     global $queryObj,$codigos_rem;
     $query = "SELECT Codigo FROM Remision WHERE Id_Remision=$id_remision";
   

     $queryObj->SetQuery($query);
     $rem = $queryObj->ExecuteQuery('simple');

     $codigos_rem.= $rem['Codigo'].',';
     return $rem['Codigo'];  
  }
  function GetCodigoActa($id_acta){
     global $queryObj;
     $query = "SELECT Codigo FROM Acta_Recepcion_Remision WHERE Id_Acta_Recepcion_Remision=$id_acta";
   

     $queryObj->SetQuery($query);
     $acta = $queryObj->ExecuteQuery('simple');

     return $acta['Codigo'];  
  }



?>





