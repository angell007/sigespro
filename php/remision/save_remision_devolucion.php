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

$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );
$productos = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );
$codigo = ( isset( $_REQUEST['codigo'] ) ? $_REQUEST['codigo'] : '' );
$rotativo = ( isset( $_REQUEST['rotativo'] ) ? $_REQUEST['rotativo'] : '' );

$datos = (array) json_decode($datos);
$productos = (array) json_decode($productos , true);
$rotativo = (array) json_decode($rotativo , true);

if($datos["Tipo"] == "Interna" && isset($rotativo["Fecha_Inicio"]) && isset($rotativo["Fecha_Fin"]))
{
     $datos["FIni_Rotativo"]=$rotativo["Fecha_Inicio"];
     $datos["FFin_Rotativo"]=$rotativo["Fecha_Fin"];
     $datos["Eps_Rotativo"]=$rotativo["Id_Eps"];
}


$refrigerados=[];
$productos_remision=[
     "Separables" => [],
     "No_Separables" => []
];
$productos_pendientes=[];


$datos['Estado']="Recibida";
$datos['Estado_Alistamiento']="2";


foreach ($productos as  $value) {
     if(count($value['Lotes_Seleccionados'])>0){

          if ($datos['Tipo_Origen'] == 'Bodega') {
               if (BodegaAplicaCategoriasSeparables($datos['Id_Origen'])) {
                    if ($value['Categoria_Separable'] == 'Si') {
                         if (!array_key_exists($value['Categoria'],$productos_remision['Separables'])) {
                              $productos_remision['Separables'][$value['Categoria']] = [$value];
                         } else {
                              array_push($productos_remision['Separables'][$value['Categoria']],$value);
                         }
                    } else {
                         array_push($productos_remision["No_Separables"],$value);
                    }
               } else {
                    array_push($productos_remision["No_Separables"],$value);
               }
               
          } else {
               array_push($productos_remision["No_Separables"],$value);
          }

     }else{
          array_push($productos_pendientes,$value);
     }
  
}

if(count($productos_remision['No_Separables'])>0){
     $item_remision=GetLongitudRemision();

     $remisiones=array_chunk($productos_remision['No_Separables'],$item_remision);
     foreach ($remisiones as  $value) {

          $id_remision=SaveEncabezado($datos,'Productos');       
          SaveProductoRemision($id_remision,$value);
          CrearActa($id_remision);
     }
}

if (count($productos_remision['Separables'])>0) {
     $item_remision=GetLongitudRemision();

     foreach ($productos_remision['Separables'] as $categoria => $value) {

          $remisiones=array_chunk($value,$item_remision);
          foreach ($remisiones as $rem) {
               $id_remision=SaveEncabezado($datos,$categoria);
               SaveProductoRemision($id_remision,$rem);
               CrearActa($id_remision);
          }
     }
}




$http_response->SetRespuesta(0, 'Guardado Correctamente', 'Se ha guardado correctamente todas las remisiones! '.trim($codigos_rem,','));
$response = $http_response->GetRespuesta();

echo json_encode($response);

function BodegaAplicaCategoriasSeparables($id_bodega) {
     global $queryObj;
   $query = "SELECT * FROM Bodega WHERE Id_Bodega = $id_bodega";  

   $queryObj->SetQuery($query);
   $res = $queryObj->ExecuteQuery('simple');

   return $res['Aplica_Separacion_Categorias'] == 'Si' ? true : false;
}

function validarCodigo($cod){
     $query = "SELECT Id_Remision FROM Remision WHERE Codigo = '$cod'";
 
     $oCon = new consulta();
     $oCon->setQuery($query);
     $resultado = $oCon->getData();
     unset($oCon);
 
     return $resultado || false;
  }


  function SaveEncabezado($modelo,$tipo){
     global $configuracion;
     if($tipo=="Refrigerados"){
          $modelo['Tipo_Bodega']="REFRIGERADOS";
     }

     $modelo['Codigo']=$configuracion->getConsecutivo('Remision','Remision');
     $modelo['Fecha']=date("Y-m-d H:i:s");

     $oItem = new complex("Remision","Id_Remision");
     foreach($modelo as $index=>$value) {
          if($value!=''){
               if($index=='Subtotal_Remision' || $index=='Impuesto_Remision' || $index=='Descuento_Remision' || $index=='Costo_Remision'){
                    $oItem->$index=number_format($value,2,".","");
               }else{
                    $oItem->$index=$value;
               }
              
          }
         
      }
      $oItem->save();
      $id_remision = $oItem->getId();
      unset($oItem);

     $qr = generarqr('remision',$id_remision,'/IMAGENES/QR/');
     $oItem = new complex("Remision","Id_Remision",$id_remision);
     $oItem->Codigo_Qr=$qr;
     $oItem->save();
     unset($oItem);
     return $id_remision;

  }


//   function GetCodigo(){
//        global $configuracion,$queryObj;
//        $codigo=$configuracion->Consecutivo('Remision');
//        $query = "SELECT Id_Remision FROM Remision WHERE Codigo = '$codigo'";
//        $queryObj->SetQuery($query);
//        $rem = $queryObj->ExecuteQuery('simple');
//        if($rem['Id_Remision']){
//           $codigo=$configuracion->Consecutivo('Remision');
//        }
//        return $codigo;
//   }

  function GetLongitudRemision(){
     global $queryObj;
     $query = "SELECT Max_Item_Remision FROM Configuracion WHERE Id_Configuracion=1";
     $queryObj->SetQuery($query);
     $rem = $queryObj->ExecuteQuery('simple');
     return $rem['Max_Item_Remision'];
  }

  function SaveProductoRemision($id_remision,$productos){
     //var_dump($productos);
     
   
     foreach($productos as $producto) {

          
        //  var_dump($producto['Lotes_Seleccionados']);
//exit;
        foreach ($producto['Lotes_Seleccionados'] as  $lote) {

             $cantidad_disponible=GetCantidadDisponible($lote['Id_Inventario'],$lote['Cantidad_Seleccionada']);
       
             $p=$lote;
             if($cantidad_disponible>0){
                  if($cantidad_disponible<$lote['Cantidad'] && $cantidad_disponible>0 ){
                       $p['Cantidad']=$cantidad_disponible;
                  }
        
                  
   
                  $subtotal=($p['Cantidad']*$producto['Precio'])+(($p['Cantidad']*$producto['Precio'])+(($p['Cantidad']*$producto['Precio'])*($producto['Impuesto']/100)));
                  $p['Subtotal']=number_format($subtotal,2,".","");
             
             
   
                  $subtotal=($p['Cantidad']*$producto['Precio'])*($producto['Descuento']/100);
                  $p['Total_Descuento']=number_format($subtotal,2,".","");
             
             
   
                  $subtotal=($p['Cantidad']*$producto['Precio'])*($producto['Impuesto']/100);
                  $p['Total_Impuesto']=number_format($subtotal,2,".","");
                
                  
                  $p['Impuesto']=$producto['Impuesto'];
                  $p['Descuento']=$producto['Descuento'];
                  $p['Cantidad_Total']=$producto['Cantidad'];
                  //quitar Cantidad Seleccionada
        
                  QuitarCantidadSeleccionada($lote['Id_Inventario'],$lote['Cantidad_Seleccionada'],$p['Cantidad']);
                
                  $oItem=new complex('Producto_Remision',"Id_Producto_Remision");
                  $p['Id_Remision']=$id_remision;
                  foreach($p as $index=>$value) {
                       $oItem->$index=$value;
                   }
        
                  $oItem->save();
                  unset($oItem); 
             }else{
                  QuitarCantidadSeleccionada($lote['Id_Inventario'],$lote['Cantidad_Seleccionada'],0);
             }
           
        }  
        
        GuardarPendientes($producto,$id_remision);
      }
      GuardarActividadRemision($id_remision);
}

function GetCantidadDisponible($id_Inventario,$cantidad){
     global $queryObj;

     $query = "SELECT (Cantidad-(Cantidad_Apartada+Cantidad_Seleccionada)) as Cantidad FROM Inventario WHERE Id_Inventario=$id_Inventario";

     $queryObj->SetQuery($query);
     $inv = $queryObj->ExecuteQuery('simple');

     return ($inv['Cantidad']+$cantidad);  
  }

  function QuitarCantidadSeleccionada($id_inventario,$cantidad,$apartada){
     global $queryObj;
     $query = "SELECT Cantidad_Seleccionada,Cantidad_Apartada  FROM Inventario WHERE Id_Inventario=$id_inventario";

     $queryObj->SetQuery($query);
     $inv = $queryObj->ExecuteQuery('simple'); 

     $cantidad_final=$inv['Cantidad_Seleccionada']-$cantidad;
     $cantidad_apartada=$inv['Cantidad_Apartada']+$apartada;

     if($cantidad_final<0){
          $cantidad_final=0;
     }
     if($cantidad_apartada<0){
          $cantidad_apartada=0;
     }
     $oItem=new complex('Inventario',"Id_Inventario",$id_inventario);
     $oItem->Cantidad_Seleccionada=number_format($cantidad_final,0,"","");
     $oItem->Cantidad_Apartada=number_format($cantidad_apartada,0,"","");
     $oItem->save();
     unset($oItem);
  }


 function  GuardarActividadRemision($id_remision){
     global $datos;

     $oItem = new complex('Actividad_Remision',"Id_Actividad_Remision");
     $oItem->Id_Remision=$id_remision;
     $oItem->Identificacion_Funcionario=$datos["Identificacion_Funcionario"];
     $oItem->Detalles="Se creo la remision con codigo ".GetCodigoRem($id_remision);
     $oItem->Fecha=date("Y-m-d H:i:s");
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


  function GuardarPendientes($p,$id){
     global $queryObj,$datos;

     if($datos['Tipo_Destino']=='Punto_Dispensacion' && $datos['Tipo_Origen']=='Bodega'){

          $query = "SELECT Id_Producto_Pendientes_Remision, Cantidad FROM Producto_Pendientes_Remision WHERE Id_Punto_Dispensacion=$datos[Id_Destino] AND Id_Producto=$p[Id_Producto] ";
          

          $queryObj->SetQuery($query);
          $prod = $queryObj->ExecuteQuery('simple');
          
          if($prod['Cantidad']){

               $oItem = new complex("Producto_Pendientes_Remision", "Id_Producto_Pendientes_Remision", $prod['Id_Producto_Pendientes_Remision']);
               
               $cantidad_diferencial = $prod['Cantidad']-$p['Cantidad']+$p['Cantidad_Pendiente'];
               if ($cantidad_diferencial <= 0) {
                  $oItem->delete();
               } else {
                   $oItem->Cantidad = number_format($cantidad_diferencial,0,"","");
                   $oItem->save();
               }
               unset($oItem);
          }else{
               if($p['Cantidad_Pendiente']>0){
                    $oItem = new complex("Producto_Pendientes_Remision", "Id_Producto_Pendientes_Remision");
                    $oItem->Id_Remision = $id=='' ? '0' : $id;
                    $oItem->Id_Producto = $p['Id_Producto'];
                    $oItem->Cantidad = $p['Cantidad_Pendiente'];
                    $oItem->Id_Punto_Dispensacion = $datos['Id_Destino'];
                    $oItem->save();
                    unset($oItem);
               }
               
          }
     }

  }
  
  EliminarBorrador();

  function EliminarBorrador(){
     global $codigo;
     
     $query = 'DELETE 
     FROM Borrador 
     WHERE Codigo="'.$codigo.'"' ;
     $oCon= new consulta();
     $oCon->setQuery($query);
     $dato = $oCon->deleteData();
     unset($oCon);
  }
  
  function CrearActa($id_remision){
     global $datos;
    $configuracion = new Configuracion();
    $cod = $configuracion->Consecutivo('Acta_Recepcion_Remision'); 
    $acta['Codigo']=$cod;
    $acta['Id_Bodega']=$datos['Id_Destino'];
    $acta['Identificacion_Funcionario']=$datos['Identificacion_Funcionario'];
    $acta['Observaciones']="Traslado de la bodega de Devoluciones a la Bodega ".$datos['Nombre_Destino'];
    $acta['Id_Remision']=$id_remision;
    $acta['Tipo']='Bodega';
    $acta['Fecha']=date("Y-m-d H:i:s");

    $oItem = new complex("Acta_Recepcion_Remision","Id_Acta_Recepcion_Remision");
    foreach($acta as $index=>$value) {
        $oItem->$index=$value;
        
    }
    $oItem->save();
    $id_acta = $oItem->getId();
    //var_dump($id_Acta_Recepcion_remision);
    unset($oItem);

    CrearQR($id_acta);
    $modelo['Id_Remision']=$id_remision;
    $modelo['Id_Acta_Recepcion_Remision']=$id_acta;
    $modelo['Id_Bodega']=$acta['Id_Bodega'];
    $modelo['Identificacion_Funcionario']=$acta['Identificacion_Funcionario'];

    RegistrarProducto($modelo);

 }

 function CrearQR($id_acta){
     $qr = generarqr('actarecepcionremision',$id_acta,'/IMAGENES/QR/');
     $oItem = new complex("Acta_Recepcion_Remision","Id_Acta_Recepcion_Remision",$id_acta);
     $oItem->Codigo_Qr=$qr;
     $oItem->save();
     unset($oItem);
  }

  function RegistrarProducto($modelo){

     $query = "SELECT PR.Id_Producto, PR.Cantidad,PR.Lote, PR.Precio,PR.Fecha_Vencimiento, PR.Id_Producto_Remision, PR.Id_Remision,$modelo[Id_Acta_Recepcion_Remision] as Id_Acta_Recepcion_Remision, P.Codigo_Cum   FROM Producto_Remision PR INNER JOIN Producto P ON PR.Id_Producto=P.Id_Producto WHERE Id_Remision=$modelo[Id_Remision] " ;
     $oCon= new consulta();
     $oCon->setQuery($query);
     $oCon->setTipo('Multiple');
     $productos = $oCon->getData();
     unset($oCon);
 
     foreach ($productos as $prod) {     
 
          //se registarn los datos en el Producto acta recepcion remision 
          $oItem = new complex('Producto_Acta_Recepcion_Remision','Id_Producto_Acta_Recepcion_Remision');
          foreach ($prod as $key => $value) {
               $oItem->$key=$value;
          }
          $oItem->save();
          unset($oItem);

          // validar si el lote ya esta en imventario 
          $query = 'SELECT I.*
          FROM Inventario I
          WHERE I.Id_Bodega='.$modelo['Id_Bodega'].' AND I.Id_Producto='.$prod['Id_Producto'].' AND I.Lote="'.$prod['Lote'].'"' ;

          $oCon= new consulta();
          $oCon->setQuery($query);
          $inventario = $oCon->getData();
          unset($oCon);

          if($inventario){
               $actual=number_format($prod["Cantidad"],0,"","");
               $suma=number_format($inventario["Cantidad"],0,"","");
               $total=$suma+$actual;
               $oItem = new complex('Inventario','Id_Inventario',$inventario['Id_Inventario']);
          $oItem->Identificacion_Funcionario = $modelo['Identificacion_Funcionario'];
          $oItem->Id_Producto= $prod['Id_Producto'];
          $oItem->Costo = (FLOAT)$prod['Precio'];
          $oItem->Codigo_CUM = $prod['Codigo_Cum'];
          $oItem->Cantidad = number_format($total,0,"","");
          $oItem->Lote = $prod['Lote'];
          $oItem->Fecha_Carga =date("Y-m-d H:i:s"); 
          $oItem->Fecha_Vencimiento = $prod['Fecha_Vencimiento'];            
          $oItem->save();
          unset($oItem);
          }else{
               $oItem = new complex('Inventario','Id_Inventario');
               $oItem->Identificacion_Funcionario = $modelo['Identificacion_Funcionario'];
               $oItem->Id_Bodega= $modelo['Id_Bodega'];
               $oItem->Id_Producto= $prod['Id_Producto'];
               $oItem->Costo = (FLOAT)$prod['Precio'];
               $oItem->Cantidad = number_format($prod['Cantidad'],0,"","");
               $oItem->Codigo_CUM = $prod['Codigo_Cum'];
               $oItem->Lote = strtoupper($prod['Lote']);
               $oItem->Fecha_Carga =date("Y-m-d H:i:s"); 
               $oItem->Fecha_Vencimiento = $prod['Fecha_Vencimiento'];            
               $oItem->save();
               unset($oItem);

          }
     }
 
     $query = 'SELECT Codigo  FROM Remision WHERE Id_Remision ='.$modelo['Id_Remision'] ;
 
     $oCon= new consulta();
     $oCon->setQuery($query);
     $rem = $oCon->getData();
     unset($oCon);
 
     $oItem = new complex('Actividad_Remision',"Id_Actividad_Remision");
     $oItem->Id_Remision = $modelo["Id_Remision"];
     $oItem->Identificacion_Funcionario = $modelo['Identificacion_Funcionario'];
     $oItem->Detalles = "Se hace el acta de recepcion de la  ".$rem["Codigo"];
     $oItem->Estado = "Recibida";
     $oItem->save();  
     unset($oItem);
  }

?>