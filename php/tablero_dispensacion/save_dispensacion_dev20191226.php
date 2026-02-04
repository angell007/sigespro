<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.http_response.php');
require_once('../../class/class.configuracion.php');
require_once('../../class/class.qr.php');
require('../../class/class.guardar_archivos.php');
include_once('../../class/class.portal_clientes.php');
include_once('../../class/class.facturaccionmasiva.php');

$storer = new FileStorer();

$queryObj = new QueryBaseDatos();
$response = array();
$http_response = new HttpResponse();
$portalClientes = new PortalCliente($queryObj);
$facturaccion=new  Facturacion_Masiva();

$configuracion = new Configuracion();

$id_disp='';
$codigo_dispensacion='';

date_default_timezone_set('America/Bogota');
$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );
$productos = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );
 

$modelo = (array) json_decode(utf8_decode($modelo));
$productos = (array) json_decode(utf8_decode($productos) , true);

unset($modelo['Id_Dispensacion']);

$imagen=$modelo["Firma_Reclamante"];
$fot='';
$idFactura=0;
if($imagen!=''){
    $fot=SaveFirma($imagen);
}
$modelo["Firma_Reclamante"]=$fot;

$id_disp=SaveEncabezado($modelo);
if(!empty($_FILES['acta']['name'])){
    SaveActa();
}


SaveProductosDispensacion($productos,$id_disp);

if($modelo['Id_Dispensacion_Fecha_Entrega']){
    ActualizarFechaEntrega($modelo['Id_Dispensacion_Fecha_Entrega']);
}else{
    CrearFechaEntrega($modelo);
}

GuardarActividad($id_disp);

if($modelo['Id_Auditoria']!=''){
    ActualizarAuditoria($modelo['Id_Auditoria'],$id_disp);
}



$http_response->SetRespuesta(0, 'Guardado Correctamente', 'Se ha guardado correctamente la dispensacion '. $codigo_dispensacion);
$response = $http_response->GetRespuesta();
$response['id_dispensacion']=$id_disp;



GuardarDispensacionPortalClientes($id_disp); //DESCOMENTAR ESTA LINEA PARA GUARDAR EN EL PORTAL CLIENTES

ValidarDispensacionFacturacion($id_disp);
$response['id_factura']=$idFactura;
echo json_encode($response);




  function SaveEncabezado($modelo){

     $modelo['Codigo']=GetCodigo();
     $modelo['Fecha_Actual']=date("Y-m-d H:i:s");

     $oItem = new complex("Dispensacion","Id_Dispensacion");
     foreach($modelo as $index=>$value) {
          if($value!='' && $index!='Pendientes' && $index != 'Cuota'){              
                $oItem->$index=$value;              
          }else if($index=='Pendientes' || $index == 'Cuota'){
              $value=(INT)($value);
            $oItem->$index=$value; 
          }
         
      }
      $oItem->save();
      $id_dis = $oItem->getId();
      unset($oItem);

     $qr = generarqr('dispensacion',$id_dis,'/IMAGENES/QR/');
     $oItem = new complex("Dispensacion","Id_Dispensacion",$id_dis);
     $oItem->Codigo_Qr=$qr;
     $oItem->save();
     unset($oItem);

     return $id_dis;

  }


  function GetCodigo(){
      global $codigo_dispensacion;
       $oItem = new complex('Configuracion','Id_Configuracion',1);
       $nc = $oItem->getData();       
       $oItem->Consecutivo=$oItem->Consecutivo+1;
       $oItem->save();
       $num_dispensacion=$nc["Consecutivo"];
       unset($oItem);
       
       $cod = "DIS".sprintf("%05d", $num_dispensacion);
       $codigo_dispensacion=$cod;
       return $cod;
  }

 function SaveProductosDispensacion($prod,$id){
     foreach ($prod as $p) {
        $p['Id_Dispensacion']=$id;
        $p['Cum']=$p['Codigo_Cum'];
       

        if (validarEntregaProducto($p["Cantidad_Entregada"],$p['Id_Inventario'])) {        
       
            if($p["Id_Inventario"]!="0"){
                $p['Id_Inventario'] = (int) $p['Id_Inventario'];
                $oItem = new complex('Inventario',"Id_Inventario",$p['Id_Inventario']);
                $inv_act=$oItem->getData();
                $cantidad = number_format((int) $inv_act["Cantidad"],0,"","");
                $cantidad_entregada = number_format((int)$p["Cantidad_Entregada"],0,"","");
                $cantidad_total = $cantidad - $cantidad_entregada;
                if($cantidad_total<0){
                    $cantidad_total=0;
                    $p['Cantidad_Entregada'] =$cantidad ;
                  
                }
                $p['Entregar_Faltante'] =$p['Cantidad_Formulada']-$p['Cantidad_Entregada'];
                $oItem->Cantidad= number_format($cantidad_total,0,"","");             
                $oItem->save();
                unset($oItem);
            }

           
            $oItem = new complex('Producto_Dispensacion',"Id_Producto_Dispensacion");
            foreach($p as $index=>$value){
                $oItem->$index=$value;
            }
            $oItem->Cantidad_Formulada_Total = $p['Cantidad_Formulada'];
            $oItem->save();
            unset($oItem);
    
        } else {
            $oItem = new complex('Producto_Dispensacion',"Id_Producto_Dispensacion");
            $productos_no_entregados[] = $p;
    
            $p['Cantidad_Entregada'] = 0;
            $p['Entregar_Faltante'] =$p['Cantidad_Formulada']-$p['Cantidad_Entregada'];
            foreach($p as $index=>$value){
                $oItem->$index=$value;
            }
            $oItem->Cantidad_Formulada_Total = $p['Cantidad_Formulada'];
            $oItem->save();
            unset($oItem);
        }
     }
 }

 function cantidadInventario($id_inventario) {

    $query = "SELECT (Cantidad-Cantidad_Apartada-Cantidad_Seleccionada) AS Cantidad FROM Inventario WHERE Id_Inventario = $id_inventario";
    
    $oCon = new consulta();
    $oCon->setQuery($query);
    $cantidad = $oCon->getData()['Cantidad'];
    unset($oCon);

    return $cantidad;
    
}
function validarEntregaProducto($cant_entrega, $id_inventario){

    $cantidad_inventario = cantidadInventario($id_inventario);

    if (($cantidad_inventario-$cant_entrega) >= 0) {
        return true;
    }

    return false;
    
}





 function  GuardarActividad($dis){
     global $modelo;

    $ActividadDis['Fecha'] = date("Y-m-d H:i:s");
    $ActividadDis["Id_Dispensacion"] = $dis;
    $ActividadDis["Identificacion_Funcionario"] = $modelo['Identificacion_Funcionario'];
    $ActividadDis["Detalle"] = "Esta dispensacion fue agregada";
    $ActividadDis["Estado"] = "Creado";
    
    $oItem = new complex("Actividades_Dispensacion","Id_Actividades_Dispensacion");
    foreach($ActividadDis as $index=>$value) {
        $oItem->$index=$value;
    }
    $oItem->save();
    unset($oItem);
  }

  function SaveFirma($imagen){
     global $MY_FILE;

    list($type, $imagen) = explode(';', $imagen);
    list(, $imagen)      = explode(',', $imagen);
    $imagen = base64_decode($imagen);

    $fot="firma".uniqid().".jpg";
    $archi=$MY_FILE . "IMAGENES/FIRMAS-DIS/".$fot;
    file_put_contents($archi, $imagen);
    chmod($archi, 0644);

    return $fot;
  }

  function SaveActa(){
     global $id_disp,$storer;  

    $nombre_archivo = $storer->UploadFileToRemoteServer($_FILES, 'store_remote_files', 'ARCHIVOS/DISPENSACION/ACTAS_ENTREGAS/');
    $nombre_archivo = $nombre_archivo[0];    
    
    if ($nombre_archivo){      
        $oItem = new complex('Dispensacion','Id_Dispensacion',$id_disp);
        $oItem->Acta_Entrega = $nombre_archivo;
        $oItem->save();
        unset($oItem);
    } 
  }

  function ActualizarFechaEntrega($id){
      global $id_disp;
      $fecha=date("Y-m-d");

      $entrega=GetDatosFechaEntrega($id);

      $oItem = new complex("Dispensacion_Fecha_Entrega","Id_Dispensacion_Fecha_Entrega",$id);
      $oItem->Fecha=$fecha;
      $oItem->Id_Dispensacion=$id_disp;
      $oItem->save();
      unset($oItem);
    /*se valida que la fecha actual sea mayor a la registrada para actualizar las fecha de las siguientes entregas*/
      if($entrega['Entrega_Actual']<$fecha){
        $entregas_faltantes=GetEntregasFaltantes($entrega);
        if(count($entregas_faltantes)>0){
            $i=0;
            foreach ($entregas_faltantes as  $value) {$i++;
                $nuevafecha = strtotime ( '+'.$i.' month' , strtotime ( $fecha ) ) ;
                $nuevafecha = date ( 'Y-m-d' , $nuevafecha );
                $oItem = new complex("Dispensacion_Fecha_Entrega","Id_Dispensacion_Fecha_Entrega",$value['Id_Dispensacion_Fecha_Entrega']);
                $oItem->Fecha_Entrega=$nuevafecha; 
                $oItem->save();
                unset($oItem);
            }
        }
      }
     


  }

  function CrearFechaEntrega($modelo){
      global $id_disp;
      $entrega_actual=(INT)$modelo['Entrega_Actual'];
      $entrega_Final=(INT)$modelo['Cantidad_Entregas'];

      $fecha = date('Y-m-d');
      

      $index=0;

      for ($i=$entrega_actual; $i <=$entrega_Final ; $i++) { 
            $oItem = new complex('Dispensacion_Fecha_Entrega',"Id_Dispensacion_Fecha_Entrega");
            $oItem->Id_Paciente=$modelo["Numero_Documento"];
            $oItem->Fecha_Formula=$modelo["Fecha_Formula"];
          if($index==0){
            
            $oItem->Fecha=$fecha;            
            $oItem->Fecha_Entrega=$fecha;            
            $oItem->Entrega_Actual=$i;            
            $oItem->Entrega_Total=$entrega_Final;
            $oItem->Id_Dispensacion=$id_disp;
            $oItem->Estado='Entregada';
           
          }else{
            $nuevafecha = strtotime ( '+'.$index.' month' , strtotime ( $fecha ) ) ;
            $nuevafecha = date ( 'Y-m-d' , $nuevafecha );
            $oItem->Fecha='0000-00-00';            
            $oItem->Fecha_Entrega=$nuevafecha;            
            $oItem->Entrega_Actual=$i;            
            $oItem->Entrega_Total=$entrega_Final;
            $oItem->Id_Dispensacion=$id_disp;
          }
          $oItem->save();
          unset($oItem);

          $index++;
      }



  }

  function GetDatosFechaEntrega($id){
    global $queryObj;

    $query="SELECT * FROM Dispensacion_Fecha_Entrega WHERE Id_Dispensacion_Fecha_Entrega=$id";
    $queryObj->SetQuery($query);
    $entrega = $queryObj->ExecuteQuery('simple');
    return $entrega;
  }

  function GetEntregasFaltantes($modelo){
    global $queryObj;
    $query="SELECT * FROM Dispensacion_Fecha_Entrega WHERE 	Id_Paciente='$modelo[Id_Paciente]' AND Fecha_Formula='$modelo[Fecha_Formula]' AND Id_Dispensacion=$modelo[Id_Dispensacion] AND Fecha='0000-00-00' ORDER BY Id_Dispensacion_Fecha_Entrega ASC";
    $queryObj->SetQuery($query);
    $entrega = $queryObj->ExecuteQuery('Multiple');
    return $entrega;
  }
 
function ActualizarAuditoria($id,$dis){
    $oItem = new complex("Auditoria","Id_Auditoria",$id);
    $oItem->Estado_Turno="Atendido";
    $oItem->Id_Dispensacion=$dis;
    $oItem->save();
    unset($oItem);
}


  function GuardarDispensacionPortalClientes($idDis){
    global $portalClientes;

    $response = $portalClientes->ActualizarDispensacion($idDis);

   }

   function ValidarDispensacionFacturacion($idDis){

    global $queryObj,$facturaccion,$modelo,$idFactura;

    $query="SELECT (SELECT Nombre FROM Tipo_Servicio WHERE Id_Tipo_Servicio=D.Id_Tipo_Servicio) as Tipo_Servicio,D.Pendientes,D.Id_Dispensacion, P.Nit as Id_Cliente,D.Id_Servicio 
    FROM Dispensacion D INNER JOIN ( SELECT Nit, Id_Paciente FROm Paciente ) P ON D.Numero_Documento=P.Id_Paciente WHERE D.Id_Dispensacion=$idDis";
    $queryObj->SetQuery($query);
    $dispensacion = $queryObj->ExecuteQuery('simple');

     

    if(strtolower( $dispensacion['Tipo_Servicio'])=='evento' &&  $dispensacion['Id_Servicio']=='1' ){
        $query="SELECT PD.Id_Dispensacion,PD.Id_Producto,(SELECT Codigo FROM Dispensacion WHERE Id_Dispensacion=PD.Id_Dispensacion) as Codigo, CONCAT(P.Principio_Activo,' ',P.Presentacion,' ',P.Concentracion,' ', P.Cantidad,' ', P.Unidad_Medida) as Nombre,P.Nombre_Comercial,P.Codigo_Cum
        FROM Producto_Dispensacion PD 
        INNER JOIN Producto P ON PD.Id_Producto=P.Id_Producto
        WHERE PD.Id_Dispensacion=$idDis AND PD.Cantidad_Formulada !=PD.Cantidad_Entregada";
    
        $queryObj->SetQuery($query);
        $pendientes = $queryObj->ExecuteQuery('Multiple');
      
      
        if(count($pendientes)==0){
            $productos_sin_precio=GetProductosSinPrecio($dispensacion,$idDis);
       
            if(count($productos_sin_precio)==0){
                if(strtolower($dispensacion['Tipo_Servicio'])=='evento'){
                    $tipo='Evento';
                }elseif(strtolower($dispensacion['Tipo_Servicio'])=='cohortes'){
                    $tipo='Cohortes';
                }else{
                    return ;
                }
              
                
                $facturaccion->Facturacion($idDis,$modelo['Identificacion_Funcionario'],$tipo);
        
                $idFactura=GetIdFactura($idDis);
                $total=GetTotalFactura($idFactura);

                if($total>30000){
                    $idFactura=0;
                }
            }
        }      
    }
    
   }

   function GetProductosSinPrecio($dispensacion,$idDis){

    global $queryObj;

    if(strtolower($dispensacion['Tipo_Servicio'])=="evento"){
        $exits=" AND NOT exists (SELECT Codigo_Cum FROM Producto_Evento WHERE Codigo_Cum=P.Codigo_Cum AND Nit_EPS=$dispensacion[Id_Cliente] AND Precio>0 )  ";
    }elseif (strtolower($dispensacion['Tipo_Servicio'])=='cohortes'){
        $exits=" AND NOT exists (SELECT Id_Producto FROM Producto_Cohorte WHERE Id_Producto=PD.Id_Producto AND Nit_EPS=$dispensacion[Id_Cliente] ) ";
    }

    $query="SELECT PD.Id_Producto,P.Nombre_Comercial,P.Codigo_Cum, IFNULL((SELECT Precio FROM Precio_Regulado WHERE Codigo_Cum=P.Codigo_Cum),0) as Precio, CONCAT(P.Principio_Activo,' ',P.Presentacion,' ',P.Concentracion,' ', P.Cantidad,' ', P.Unidad_Medida) as Nombre
    FROM Producto_Dispensacion PD 
    INNER JOIN Producto P ON PD.Id_Producto=P.Id_Producto
    WHERE PD.Id_Dispensacion=$idDis ".$exits." GROUP BY PD.Id_Producto HAVING Precio=0 ";
    

    $queryObj->SetQuery($query);
    $productos_sin_precio = $queryObj->ExecuteQuery('Multiple');

    return $productos_sin_precio;
}
function GetIdFactura($idDis){
    global $queryObj;

    $query="SELECT Id_Factura FROM Factura WHERE Id_Dispensacion=$idDis";
    $queryObj->SetQuery($query);
    $fact = $queryObj->ExecuteQuery('simple');



    return $fact['Id_Factura'];
}
function GetTotalFactura($id){
    global $queryObj;

    $query="SELECT SUM(Subtotal) as Total FROM Producto_Factura WHERE Id_Factura=$id";
    $queryObj->SetQuery($query);
    $fact = $queryObj->ExecuteQuery('simple');



    return $fact['Total']; 
}


?>





