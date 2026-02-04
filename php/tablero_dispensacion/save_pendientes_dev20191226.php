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

$configuracion = new Configuracion();
 $portalClientes = new PortalCliente($queryObj);
 $facturaccion=new  Facturacion_Masiva();

$id_disp='';

date_default_timezone_set('America/Bogota');

$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );
$productos = ( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );
$modelo=utf8_decode($modelo);
$modelo = (array) json_decode($modelo);
$productos = (array) json_decode(utf8_decode($productos) , true);



$productos_no_entregados=[];
$imagen=$modelo["Firma_Reclamante"];
$fot='';
if($imagen!=''){
    $fot=SaveFirma($imagen);
    $oItem=new complex('Dispensacion','Id_Dispensacion',$modelo['Id_Dispensacion']);
    $oItem->Firma_Reclamante=$fot;
    $oItem->save();
    unset($oItem);
}
$modelo["Firma_Reclamante"]=$fot;


if(!empty($_FILES['acta']['name'])){
    SaveActa($_FILES['acta']['name']);
}

$idFactura=0;


SaveProductosDispensacion($productos);

if (count($productos_no_entregados) == count($productos)) {
    $http_response->SetRespuesta(2, 'Guardado Correctamente', 'No se entregó ningun pendiente debido a que todos los productos seleccionados no tienen inventario. ');
    $response = $http_response->GetRespuesta();
    $response['productos_no_entregados'] = $productos_no_entregados;
} elseif (getStatus() == 1) {
    $http_response->SetRespuesta(0, 'Guardado Correctamente', 'Se ha guardado correctamente la dispensación pendiente.');
    $response = $http_response->GetRespuesta();
    $response['productos_no_entregados'] = $productos_no_entregados;
} else {
    $http_response->SetRespuesta(0, 'Guardado Correctamente', 'Se ha guardado correctamente la dispensación pendiente.');
    $response = $http_response->GetRespuesta();
    $response['productos_no_entregados'] = $productos_no_entregados;
}

GuardarDispensacionPortalClientes($modelo['Id_Dispensacion']); 

ValidarDispensacionFacturacion($modelo['Id_Dispensacion']);
$response['id_factura']=$idFactura;
echo json_encode($response);


 function SaveProductosDispensacion($prod){
     global $productos_no_entregados;
     foreach ($prod as $p) {
        if (validarEntregaProducto($p["Cantidad_Entregada"],$p['Id_Inventario'])) { 
            $prod_disp=GetProducto($p);    
            
           
            if($prod_disp['Id_Producto_Dispensacion']){

                $p['Id_Producto'] = isset($p['Id_Producto_Antiguo']) ? $p['Id_Producto'] : $p['Id_Producto'];

                if($prod_disp['Cantidad_Entregada']>0){
                    $oItem=new complex ("Producto_Dispensacion","Id_Producto_Dispensacion",$prod_disp['Id_Producto_Dispensacion']);
                    $oItem->Cantidad_Formulada=$prod_disp['Cantidad_Entregada'];
                    $oItem->save();    
                    unset($oItem);

                    $p['Cantidad_Formulada']=$prod_disp['Cantidad_Pendiente'];
                    unset($p['Id_Producto_Dispensacion']);

                    $oItem = new complex("Producto_Dispensacion","Id_Producto_Dispensacion");
                    foreach ($p as $index => $value) {
                        if($value!=''){
                            $oItem->$index=$value;
                            if($index=='Codigo_Cum'){
                                $oItem->Cum=$value;
                            }
                        }                    
                    }

                    $oItem->save();
                    $id_producto_pendiente=$oItem->getId();
                    unset($oItem);

                }else{
                    $oItem=new complex ("Producto_Dispensacion","Id_Producto_Dispensacion",$p['Id_Producto_Dispensacion']);
                    $oItem->Cantidad_Entregada=$p['Cantidad_Entregada'];
                    $oItem->Lote=$p['Lote'];
                    $oItem->Fecha_Vencimiento=$p['Fecha_Vencimiento'];
                    $oItem->Id_Inventario=$p['Id_Inventario'];
                    $oItem->Cum=$p['Codigo_Cum'];
                    $oItem->Id_Producto=$p['Id_Producto'];
                    $oItem->save();
                    $id_producto_pendiente=$oItem->getId();
                    unset($oItem);
                }

                $oItem = new complex('Producto_Dispensacion_Pendiente',"Id_Producto_Dispensacion_Pendiente");
                $cantidad_pendiente=$p["Cantidad_Formulada"]-$p["Cantidad_Entregada"];
                $oItem->Id_Producto_Dispensacion=$id_producto_pendiente;
                $oItem->Cantidad_Entregada=$p["Cantidad_Entregada"];
                $oItem->Cantidad_Pendiente=$cantidad_pendiente ;
                $oItem->Entregar_Faltante=$cantidad_pendiente;
                $oItem->save();
                unset($oItem);

                if($p["Id_Inventario"]!="0"){
                    $p['Id_Inventario'] = (int) $p['Id_Inventario'];
                    $oItem = new complex('Inventario',"Id_Inventario",$p['Id_Inventario']);
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
                DescontarPendientes($p['Id_Dispensacion'],$p['Cantidad_Entregada']);

                GuardarActividad($p);

                RegistarCambioProducto($p);
            }
    
        } else {            
            $productos_no_entregados[] = $p;           
       
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
    $ActividadDis["Id_Dispensacion"] = $dis['Id_Dispensacion'];
    $ActividadDis["Identificacion_Funcionario"] = $modelo['Identificacion_Funcionario'];

    $ActividadDis["Detalle"] = "Se entrego la dispensacion pendiente. Producto: $dis[Nombre_Comercial] - Cantidad: $dis[Cantidad_Entregada]" ;
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
  function DescontarPendientes($dis,$cantidad){

    $oItem = new complex('Dispensacion',"Id_Dispensacion", $dis);
    $pendientes = $oItem->Pendientes - $cantidad;
    $entregados = $oItem->Productos_Entregados + $cantidad;
    if ($pendientes >= 0) {
        $oItem->Pendientes = number_format($pendientes,0,"","");
        $oItem->Productos_Entregados = number_format($entregados,0,"","");
    } else { // Evitar por si cae en negativo.
        $oItem->Pendientes = '0';
        $oItem->Productos_Entregados = number_format($entregados,0,"","");
    }
    $oItem->save();
    unset($oItem);

  }


 function GetProducto($prod){
     
    global $queryObj;
    $id_producto = isset($prod['Id_Producto_Antiguo']) ? $prod['Id_Producto_Antiguo'] : $prod['Id_Producto'];

    $query="SELECT *,(Cantidad_Formulada-Cantidad_Entregada) as Cantidad_Pendiente
    FROM Producto_Dispensacion WHERE Id_Dispensacion=$prod[Id_Dispensacion] AND Id_Producto=$id_producto HAVING Cantidad_Pendiente>0 " ;


    $queryObj->SetQuery($query);
    $pd=$queryObj->ExecuteQuery('simple');

    return $pd;
 }

 function getStatus() {
    global $productos_no_entregados;

    if (count($productos_no_entregados) > 0) {
        return 1;
    } else {
        return 2;
    }
}

function RegistarCambioProducto($p){
    global $modelo;
    if(isset($p['Id_Producto_Antiguo'])){
        $oItem=new complex("Cambio_Producto_Dispensacion","Id_Cambio_Producto_Dispensacion");
        $oItem->Id_Producto_Nuevo=$p['Id_Producto'];
        $oItem->Id_Producto_Antiguo=$p['Id_Producto_Antiguo'];
        $oItem->Id_Dispensacion=$p['Id_Dispensacion'];
        $oItem->Identificacion_Funcionario=$modelo['Identificacion_Funcionario'];
        $oItem->save();
        unset($oItem);

        
    }
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

     

    if( strtolower( $dispensacion['Tipo_Servicio'])=='evento' && $dispensacion['Id_Servicio']=='1' ){
        


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





