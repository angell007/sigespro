<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.http_response.php');
require_once('../../class/class.configuracion.php');
require_once('../../class/class.qr.php');
require('../../class/class.guardar_archivos.php');
//include_once('../../class/class.portal_clientes.php');
include_once('../../class/class.facturaccionmasiva.php');
include_once('../../class/class.mipres.php');

$storer = new FileStorer();
$mipres= new Mipres();
$queryObj = new QueryBaseDatos();
$response = array();
$http_response = new HttpResponse();

//$portalClientes = new PortalCliente($queryObj);

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
   // SaveActa();
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

//GuardarDispensacionPortalClientes($id_disp); //DESCOMENTAR ESTA LINEA PARA GUARDAR EN EL PORTAL CLIENTES

ValidarDispensacionFacturacion($id_disp);
$response['id_factura']=$idFactura;


if ($modelo['Id_Tipo_Servicio'] == 3 || $modelo['Id_Tipo_Servicio'] == 5 || $modelo['Id_Tipo_Servicio'] == 2) {
    ValidarDispensacionMipres($id_disp, $modelo['Id_Dispensacion_Mipres'] ?? null);
}

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
    global $modelo;
    $prods_mipres = '';
    $id_dis_mipres = '';
     foreach ($prod as $p) {
        $p['Id_Dispensacion']=$id;
        $p['Id_Producto_Mipres']=$p['Id_Producto'];
        $p['Cum']=$p['Codigo_Cum'];   
      
        /** Modifcado el 20 de Julio 2020 Augusto - Cambia Inventario por Inventario_Nuevo */
        if (validarEntregaProducto($p["Cantidad_Entregada"],$p['Id_Inventario_Nuevo'])) {    
            if($p["Id_Inventario_Nuevo"]!="0"){
                $p['Id_Inventario_Nuevo'] = (int) $p['Id_Inventario_Nuevo'];
                $oItem = new complex('Inventario_Nuevo',"Id_Inventario_Nuevo",$p['Id_Inventario_Nuevo']);
                $inv_act=$oItem->getData();
                $cantidad = number_format((int) $inv_act["Cantidad"],0,"","");
                $cantidad_entregada = number_format($p["Cantidad_Entregada"],0,"","");
                $cantidad_total = $cantidad - $cantidad_entregada;
                if($cantidad_total<0){
                    $cantidad_total=0;
                    $p['Cantidad_Entregada'] =$cantidad ;
                    $p['Entregar_Faltante'] =$cantidad_entregada-$cantidad;
                }

                $oItem->Cantidad= number_format($cantidad_total,0,"","");    
                $oItem->save();
                unset($oItem);

            }
            $oItem = new complex('Producto_Dispensacion',"Id_Producto_Dispensacion");
            foreach($p as $index=>$value){
                $oItem->$index=$value;
            }
            $oItem->Cantidad_Formulada_Total = $p['Cantidad_Formulada'];
            if (isset($p['Id_Producto_Dispensacion_Mipres']) && $p['Id_Producto_Dispensacion_Mipres'] != '') {
                $oItem->Id_Producto_Mipres = $p['Id_Producto'];
                $id_dis_mipres=ActualizaProductoDispensacionMipres($p['Id_Producto_Dispensacion_Mipres'],$p);
                $prods_mipres.=$p['Id_Producto_Dispensacion_Mipres'].",";
            }
            $oItem->save();
            unset($oItem);
        } else {

            $oItem = new complex('Producto_Dispensacion',"Id_Producto_Dispensacion");
            $productos_no_entregados[] = $p;
            $p['Cantidad_Entregada'] = 0;
            foreach($p as $index=>$value){
                $oItem->$index=$value;
            }
            $oItem->Cantidad_Formulada_Total = $p['Cantidad_Formulada'];
            if (isset($p['Id_Producto_Dispensacion_Mipres']) && $p['Id_Producto_Dispensacion_Mipres'] != '') {
                $oItem->Id_Producto_Mipres = $p['Id_Producto'];
                $id_dis_mipres=ActualizaProductoDispensacionMipres($p['Id_Producto_Dispensacion_Mipres'],$p);
                $prods_mipres.=$p['Id_Producto_Dispensacion_Mipres'].",";
            }
            $oItem->save();
            unset($oItem);

        }

        if (isset($p['Id_Producto_Dispensacion_Mipres']) && $p['Id_Producto_Dispensacion_Mipres'] != '') {
            updateProductoDispensacionMipres($p['Id_Producto_Dispensacion_Mipres'],$p['Id_Producto']);
        }
     }
    if ($prods_mipres!='') {
        ValidarDispensacionMipres(trim($prods_mipres,","),$modelo["Id_Dispensacion_Mipres"]);
        ActualizaProductosDispensacionMipres(trim($prods_mipres,","),$modelo["Id_Dispensacion_Mipres"],$id);
    }
 }



 function cantidadInventario($id_inventario_nuevo) {

    /** Modifcado el 20 de Julio 2020 Augusto - Cambia Inventario por Inventario_Nuevo */

    $query = "SELECT (Cantidad-Cantidad_Apartada-Cantidad_Seleccionada) AS Cantidad FROM Inventario_Nuevo WHERE Id_Inventario_Nuevo = $id_inventario_nuevo";

    

    $oCon = new consulta();

    $oCon->setQuery($query);

    $cantidad = $oCon->getData()['Cantidad'];

    unset($oCon);



    return $cantidad;

    

}

function validarEntregaProducto($cant_entrega, $id_inventario_nuevo){
/** Modifcado el 20 de Julio 2020 Augusto - Cambia Inventario por Inventario_Nuevo */


    $cantidad_inventario_nuevo = cantidadInventario($id_inventario_nuevo);



    if (($cantidad_inventario_nuevo-$cant_entrega) >= 0) {

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



   // $response = $portalClientes->ActualizarDispensacion($idDis);



   }



   function ValidarDispensacionFacturacion($idDis){

/*

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


                if($total>300000){
                    $idFactura=0;
                }
            }
        }      
    }
*/
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
function ActualizaProductosDispensacionMipres($entregados,$id_dis_mipres,$id_dis){
    global $queryObj,$mipres,$reclamante;
    $codigo_sede_mp=GetCodigoSede();
    $nit_mp=GetNitProh();
    $productos = GetProductosDispensacionMipres($id_dis);
    
    foreach($productos as $prod){
        $data['ID']=(INT)$prod['ID'];
        $data['CodSerTecEntregado']=$prod['Cum'];
        $data['CantTotEntregada']=$prod['Entregada'];
        $data['EntTotal']=0;
        $data['CausaNoEntrega']=0;
        $data['FecEntrega']=date('Y-m-d');
        $data['NoLote']=$prod["Lote"];
        $data['TipoIDRecibe']=$reclamante['Codigo'];
        $data['NoIDRecibe']=$reclamante['Id_Reclamante'];
        $entrega=$mipres->ReportarEntrega($data);
        if($entrega[0]['Id']){
            $oItem=new complex('Producto_Dispensacion_Mipres','Id_Producto_Dispensacion_Mipres',$prod['Id_Producto_Dispensacion_Mipres']);
            $oItem->IdEntrega=$entrega[0]['IdEntrega'];
            $oItem->Fecha_Entrega = date("Y-m-d H:i:s");
            $oItem->save();
            unset($oItem);
            
            $oItem=new complex('Dispensacion_Mipres','Id_Dispensacion_Mipres',$prod['Id_Dispensacion_Mipres']);
            $oItem->Estado='Entregado';
            $oItem->save();
            unset($oItem);
        }
    }
    if(count($productos)==0){
        $oItem=new complex('Dispensacion_Mipres','Id_Dispensacion_Mipres',$id_dis_mipres);
        $oItem->Estado='Radicado Programado';
        $oItem->save();
        unset($oItem);
    }
    
}

function GetProductosDispensacionMipres($id_dis){
    global $queryObj;
    $query = 'SELECT SUM(PD.Cantidad_Formulada) AS Formulada, SUM(PD.Cantidad_Entregada) AS Entregada, PDM.ID, PD.Id_Dispensacion, PD.Cum, PD.Lote, PDM.Id_Producto_Dispensacion_Mipres, PDM.Id_Dispensacion_Mipres
    FROM Producto_Dispensacion PD 
    INNER JOIN Producto_Dispensacion_Mipres PDM ON PDM.Id_Producto_Dispensacion_Mipres = PD.Id_Producto_Dispensacion_Mipres
    WHERE PD.Id_Dispensacion='.$id_dis.'
    GROUP BY PD.Id_Producto_Dispensacion_Mipres
    HAVING Entregada = Formulada
    ';

    $queryObj->SetQuery($query);
    $productos = $queryObj->ExecuteQuery('Multiple');
    return $productos;
}

function ActualizaProductoDispensacionMipres($id_mipres,$producto){
    global $queryObj,$mipres,$reclamante;

    
    $codigo_sede_mp=GetCodigoSede();
    $nit_mp=GetNitProh();
    $pm = GetProductoDispensacionMipres($id_mipres);
    $data_mp['ID']=(INT)$pm['ID'];
    $data_mp['FecMaxEnt']=$pm['Fecha_Maxima_Entrega'];
    $data_mp['TipoIDSedeProv']='NI';
    $data_mp['NoIDSedeProv']=$nit_mp;
    $data_mp['CodSedeProv']=$codigo_sede_mp;
    $data_mp['CodSerTecAEntregar']=$pm['CodSerTecAEntregar'];
    $data_mp['CantTotAEntregar']=$pm['Cantidad'];
    $respuesta=$mipres->Programacion($data_mp);
    if($respuesta[0]['Id']){
        $oItem=new complex('Producto_Dispensacion_Mipres','Id_Producto_Dispensacion_Mipres',$id_mipres);
        $oItem->IdProgramacion=$respuesta[0]['IdProgramacion'];
        $oItem->Fecha_Programacion = date("Y-m-d H:i:s");
        $oItem->save();
        unset($oItem);
        
        $oItem=new complex('Dispensacion_Mipres','Id_Dispensacion_Mipres',$pm["Id_Dispensacion_Mipres"]);
        $oItem->Estado="Programado";
        $oItem->save();
        unset($oItem);
    }
    
    if($producto["Cantidad_Entregada"]>0){
        $data['ID']=(INT)$pm['ID'];
        $data['CodSerTecEntregado']=$producto['Cum'];
        $data['CantTotEntregada']=$producto['Cantidad_Entregada'];
        $data['EntTotal']=0;
        $data['CausaNoEntrega']=0;
        $data['FecEntrega']=date('Y-m-d');
        $data['NoLote']=$producto["Lote"];
        $data['TipoIDRecibe']=$reclamante['Codigo'];
        $data['NoIDRecibe']=$reclamante['Id_Reclamante'];
        $entrega=$mipres->ReportarEntrega($data);
        if($entrega[0]['Id']){
            $oItem=new complex('Producto_Dispensacion_Mipres','Id_Producto_Dispensacion_Mipres',$pm['Id_Producto_Dispensacion_Mipres']);
            $oItem->IdEntrega=$entrega[0]['IdEntrega'];
            $oItem->Fecha_Entrega = date("Y-m-d H:i:s");
            $oItem->save();
            unset($oItem);
            
            $oItem=new complex('Dispensacion_Mipres','Id_Dispensacion_Mipres',$pm['Id_Dispensacion_Mipres']);
            $oItem->Estado='Entregado';
            $oItem->save();
            unset($oItem);
        }
        
    }
    
    return ($pm["Id_Dispensacion_Mipres"]);
}
function ValidarDispensacionMipres($ids,$mipres){
    global $queryObj;
    $query = 'SELECT D.*, GROUP_CONCAT(Id_Producto_Dispensacion_Mipres) as Productos
    
    FROM Producto_Dispensacion_Mipres PD 
    INNER JOIN Dispensacion_Mipres D ON PD.Id_Dispensacion_Mipres=D.Id_dispensacion_Mipres
    WHERE PD.Id_Producto_Dispensacion_Mipres NOT IN ('.$ids.') AND PD.Id_Dispensacion_Mipres='.$mipres;

    //echo $query;
    
    $queryObj->SetQuery($query);
    $datos_mipres = $queryObj->ExecuteQuery('simple');
    
    if($datos_mipres){
     
       unset($datos_mipres["Id_Dispensacion_Mipres"]);
       
       unset($datos_mipres["NoSubEntrega"]);
       
        $oItem = new complex('Dispensacion_Mipres',"Id_Dispensacion_Mipres");
        $datos_mipres["Estado"]="Programado";
        $datos_mipres["Bandera"]="Separado";
        foreach($datos_mipres as $index=>$value){
            
            $oItem->$index=$value;
        }
        
        $oItem->save();
        $id_nuevo = $oItem->getId();
        //echo " -- separo"; 
        unset($oItem); 
        if($id_nuevo && $datos_mipres["Productos"]!=''){
           // echo " -- actualizo productos mipres";
            $query="UPDATE Producto_Dispensacion_Mipres SET Id_Dispensacion_Mipres=".$id_nuevo." WHERE Id_Producto_Dispensacion_Mipres IN (".$datos_mipres["Productos"].")";
            $oCon = new consulta();
            $oCon->setQuery($query);
            $oCon->createData();
            unset($oCon);
        }
        
    }
}
/*
function ValidarDispensacionMipres($idDis){

    global $queryObj,$mipres,$modelo;
    $pendientes=GetPendientes($idDis);
    $codigo_sede=GetCodigoSede();
    $nit=GetNitProh();
    if($modelo['Id_Auditoria']!=''){
        $reclamante=GetReclamante();
    }
   
    if(count($pendientes)==0){

        $dispensacion=GetDispensacion($idDis);
        if($dispensacion['Id_Dispensacion_Mipres']!='0'){
            $oItem = new complex("Dispensacion_Mipres","Id_Dispensacion_Mipres",$modelo["Id_Dispensacion_Mipres"]);
            $oItem->Estado='Entregado';
            $oItem->save();
            unset($oItem);
            $productos_mipres=GetProductosMipres($dispensacion['Id_Dispensacion_Mipres']);
            foreach ($productos_mipres as  $pm) {
                $data['ID']=(INT)$pm['ID'];
                $data['FecMaxEnt']=$pm['Fecha_Maxima_Entrega'];
                $data['TipoIDSedeProv']='NI';
                $data['NoIDSedeProv']=$nit;
                $data['CodSedeProv']=$codigo_sede;
                $data['CodSerTecAEntregar']=$pm['CodSerTecAEntregar'];
                $data['CantTotAEntregar']=$pm['Cantidad'];
                $respuesta=$mipres->Programacion($data);
                if($respuesta[0]['Id']){
                    $oItem=new complex('Producto_Dispensacion_Mipres','Id_Producto_Dispensacion_Mipres',$pm['Id_Producto_Dispensacion_Mipres']);
                    $oItem->IdProgramacion=$respuesta[0]['IdProgramacion'];
                    $oItem->save();
                    unset($oItem);
                }
                $lote=GetLoteEntregado($pm['Id_Producto'],$idDis);
                $data['ID']=(INT)$pm['ID'];
                $data['CodSerTecEntregado']=$pm['CodSerTecAEntregar'];
                $data['CantTotEntregada']=$pm['Cantidad'];
                $data['EntTotal']=0;
                $data['CausaNoEntrega']=0;
                $data['FecEntrega']=date('Y-m-d');
                $data['NoLote']=$lote;
                $data['TipoIDRecibe']='CC';
                $data['NoIDRecibe']= $reclamante;
                $entrega=$mipres->ReportarEntrega($data);

                if($entrega[0]['Id']){
                    $oItem=new complex('Producto_Dispensacion_Mipres','Id_Producto_Dispensacion_Mipres',$pm['Id_Producto_Dispensacion_Mipres']);
                    $oItem->IdEntrega=$entrega[0]['IdEntrega'];
                    $oItem->save();
                    unset($oItem);

                }

            }



            $oItem=new complex('Dispensacion_Mipres','Id_Dispensacion_Mipres',$dispensacion['Id_Dispensacion_Mipres']);

            $oItem->Estado='Entregado';

            $oItem->save();

            unset($oItem);



        }

    }

}

*/

function GetPendientes($idDis){

    global $queryObj;



    $query="SELECT PD.Id_Dispensacion,PD.Id_Producto,(SELECT Codigo FROM Dispensacion WHERE Id_Dispensacion=PD.Id_Dispensacion) as Codigo, CONCAT(P.Principio_Activo,' ',P.Presentacion,' ',P.Concentracion,' ', P.Cantidad,' ', P.Unidad_Medida) as Nombre,P.Nombre_Comercial,P.Codigo_Cum

    FROM Producto_Dispensacion PD 

    INNER JOIN Producto P ON PD.Id_Producto=P.Id_Producto

    WHERE PD.Id_Dispensacion=$idDis AND PD.Cantidad_Formulada !=PD.Cantidad_Entregada";



    $queryObj->SetQuery($query);

    $pendientes = $queryObj->ExecuteQuery('Multiple');



    return $pendientes;

}



function GetDispensacion($idDis){

    global $queryObj;



    $query="SELECT Id_Dispensacion_Mipres,Id_Dispensacion FROM Dispensacion WHERE Id_Dispensacion=$idDis";

    $queryObj->SetQuery($query);

    $dispensacion = $queryObj->ExecuteQuery('simple');







    return $dispensacion; 

}



function GetProductosMipres($id){

    global $queryObj;

    $query = 'SELECT

    PD.*, D.Fecha_Maxima_Entrega		

    FROM Producto_Dispensacion_Mipres PD INNER JOIN Dispensacion_Mipres D ON PD.Id_Dispensacion_Mipres=D.Id_dispensacion_Mipres

    WHERE

    PD.Id_Dispensacion_Mipres='.$id;



    $queryObj->SetQuery($query);

    $productos = $queryObj->ExecuteQuery('Multiple');

    return $productos;

}

function GetProductoDispensacionMipres($id){
    global $queryObj;
    $query = 'SELECT
    PD.*, D.Fecha_Maxima_Entrega, D.Id_Dispensacion_Mipres		
    FROM Producto_Dispensacion_Mipres PD 
    INNER JOIN Dispensacion_Mipres D ON PD.Id_Dispensacion_Mipres=D.Id_Dispensacion_Mipres
    WHERE PD.Id_Producto_Dispensacion_Mipres='.$id;

    $queryObj->SetQuery($query);
    $productos = $queryObj->ExecuteQuery('simple');
    return $productos;
}

function GetCodigoSede(){

    global $queryObj;



    $query = '

        SELECT

            Codigo_Sede				

        FROM Configuracion

        WHERE

            Id_Configuracion=1';



    $queryObj->SetQuery($query);

    $dato = $queryObj->ExecuteQuery('simple');

    return $dato['Codigo_Sede'];

}



function GetNitProh(){

    global $queryObj;

    $query = '

        SELECT

            NIT				

        FROM Configuracion

        WHERE

            Id_Configuracion=1';



    $queryObj->SetQuery($query);

    $dato = $queryObj->ExecuteQuery('simple');



    $n=explode('-',$dato['NIT']);

    $nit=$n[0];

    $nit=str_replace('.','',$nit);

    return $nit;

    

}



function GetLoteEntregado($idProducto,$idDis){

    global $queryObj;



    $query = "SELECT Lote 

        From Producto_Dispensacion 

        WHERE Id_Producto_Mipres=$idProducto AND Id_Dispensacion=$idDis ";



    $queryObj->SetQuery($query);

    $lote = $queryObj->ExecuteQuery('simple');



    return $lote['Lote'];

}



function GetReclamante(){

    global $queryObj,$modelo;



    $query = "SELECT Identificacion_Persona FROM Auditoria A INNER JOIN  Turnero T ON A.Id_Auditoria=T.Id_Auditoria

    WHERE A.Id_Auditoria=$modelo[Id_Auditoria] ";



    $queryObj->SetQuery($query);

    $persona = $queryObj->ExecuteQuery('simple');



    if($persona){

        return $persona['Identificacion_Persona'];

    }else{

        return $modelo['Numero_Documento'];

    }

}

function updateProductoDispensacionMipres($id_producto_mipres, $id_producto) {
    $oItem = new complex('Producto_Dispensacion_Mipres','Id_Producto_Dispensacion_Mipres',$id_producto_mipres);
    $oItem->Id_Producto = $id_producto;
    $oItem->save();
    unset($oItem);
}



?>











