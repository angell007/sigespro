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
 

$datos = (array) json_decode($datos);
$productos = (array) json_decode($productos , true);


$id_devolucion=SaveEncabezado($datos);       
SaveProductoRemision($id_devolucion,$productos);




$http_response->SetRespuesta(0, 'Guardado Correctamente', 'Se ha guardado correctamente la devolucion Interna! <br> '.trim($codigos_rem,','));
$response = $http_response->GetRespuesta();

echo json_encode($response);


function validarCodigo($cod){
     $query = "SELECT Id_Remision FROM Remision WHERE Codigo = '$cod'";
  
     $oCon = new consulta();
     $oCon->setQuery($query);
     $resultado = $oCon->getData();
     unset($oCon);
 
     return $resultado || false;
  }


  function SaveEncabezado($modelo){

   
     
     $modelo['Fecha']=date("Y-m-d H:i:s");
     $oItem = new complex("Devolucion_Interna","Id_Devolucion_Interna");
     foreach($modelo as $index=>$value) {
          if($value!=''){
               if($index=='Costo_Devolucion'){
                    $oItem->$index=number_format($value,2,".","");
               }else{
                    $oItem->$index=$value;
               }
              
          }
         
      }
      $oItem->Codigo=GetCodigo();
      $oItem->save();
      $id_devolucion = $oItem->getId();
      unset($oItem);

     $qr = generarqr('devolucion_interna',$id_devolucion,'/IMAGENES/QR/');
     $oItem = new complex("Devolucion_Interna","Id_Devolucion_Interna",$id_devolucion);
     $oItem->Codigo_Qr=$qr;
     $oItem->save();
     unset($oItem);
     return $id_devolucion;

  }


  function GetCodigo(){
       global $configuracion,$queryObj;
       $codigo=$configuracion->Consecutivo('Devolucion_Interna');
       sleep(2); // Esperar 2 segundo antes de hacer la validación.
       $query = "SELECT Id_Devolucion_Interna FROM Devolucion_Interna WHERE Codigo = '$codigo'";
       $queryObj->SetQuery($query);
       $rem = $queryObj->ExecuteQuery('simple');
       if($rem['Id_Devolucion_Interna']){
          $codigo=$configuracion->Consecutivo('Devolucion_Interna');
       }
       return $codigo;
  }



  function SaveProductoRemision($id_devolucion,$productos){
      
   
       foreach ($productos as $producto) {
                  
                    $oItem=new complex('Producto_Devolucion_Interna',"Id_Producto_Devolucion_Interna");
                    $p=$producto;
                    $p['Id_Devolucion_Interna']=$id_devolucion;
                    foreach($p as $index=>$value) {
                         $oItem->$index=$value;
                     }
                    $oItem->Cantidad=$p['Cantidad'];
                    $oItem->Precio=number_format($producto['Precio'],2,".","");
                    $oItem->save();
                    unset($oItem); 
         
        }
        GuardarActividadDevolucionInterna($id_devolucion);
  }




 function  GuardarActividadDevolucionInterna($id_devolucion){
     global $datos;

     $oItem = new complex('Actividad_Devolucion_Interna',"Id_Actividad_Devolucion_Interna");
     $oItem->Id_Devolucion_Interna=$id_devolucion;
     $oItem->Identificacion_Funcionario=$datos["Identificacion_Funcionario"];
     $oItem->Detalles="Se creo la devolucion Interna con codigo ".GetCodigoDevolucion($id_devolucion);
     $oItem->Fecha=date("Y-m-d H:i:s");
     $oItem->save();
     unset($oItem);
  }

  function GetCodigoDevolucion($id_devolucion){
     global $queryObj,$codigos_rem;
     $query = "SELECT Codigo FROM Devolucion_Interna WHERE Id_Devolucion_Interna=$id_devolucion";
   

     $queryObj->SetQuery($query);
     $rem = $queryObj->ExecuteQuery('simple');

     $codigos_rem.= $rem['Codigo'].',';
     return $rem['Codigo'];  
  }



?>





