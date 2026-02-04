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

date_default_timezone_set('America/Bogota');

$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );
$productos=( isset( $_REQUEST['productos'] ) ? $_REQUEST['productos'] : '' );
$datos=( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : '' );

$modelo = (array) json_decode(utf8_decode($modelo));

$productos = (array) json_decode(utf8_decode($productos),true);
$datos = (array) json_decode(utf8_decode($datos));
$codigo ='';
$texto='';

// echo json_encode($modelo); exit;

if($modelo['tipoCierre'] && $modelo['tipoCierre']=='Devolver'){
    $codigo=GetCodigoAjuste();
    $id_ajuste = SaveEncabezadoAjuste($datos, $modelo, $codigo);
    SaveProductosAjuste($id_ajuste,$productos);
}else{
    $id_remision=SaveEncabezado($datos,$modelo);
    SaveProductoRemision($id_remision,$productos);
    if($modelo['Tipo']=='Error_Carga'){
        $texto=' Con estado Enviada';
    }else{
         $texto=' Con estado Alistada';
    }
    $codigo = GetCodigoRem($id_remision);
    
    
    
}

$http_response->SetRespuesta(0, 'Guardado Correctamente', "Se ha guardado correctamente con codigo: $codigo $texto");
$response = $http_response->GetRespuesta();



echo json_encode($response);

function GetProductosNoConforme($id){
    global $queryObj;
    $query="SELECT PRN.Id_Producto_Remision,PRN.Cantidad,PR.Fecha_Vencimiento,PR.Lote,PRN.Cantidad as Cantidad_Total,
     PR.Precio, (PRN.Cantidad*PR.Precio) as Subtotal, PR.Id_Inventario,PR.Nombre_Producto 
      FROM Producto_No_Conforme_Remision PRN INNER JOIN Producto_Remision PR On PRN.Id_Producto_Remision=PR.Id_Producto_Remision WHERE PRN.Id_No_Conforme=$id ";
    $queryObj->SetQuery($query);
    $productos = $queryObj->ExecuteQuery('Multiple');
    
    return $productos;
}

function SaveEncabezado($modelo,$datos){
    global $configuracion;
  
    $modelo['Identificacion_Funcionario']=$datos['Identificacion_Funcionario'];
    $modelo['Estado']=$datos['Tipo']=='Error_Bodega' ? 'Alistada' : 'Enviada';
    $modelo['Estado_Alistamiento']=2;
    $modelo['Fecha']=date("Y-m-d H:i:s");
    $modelo['Id_Categoria']= '0';
    $modelo['Observaciones']= 'Reenvio de los medicamentos en repuesta al no conforme '.$modelo['Codigo'];
    $oItem = new complex("Remision","Id_Remision");
    foreach($modelo as $index=>$value) {
         if($value!=''){            
            $oItem->$index=$value; 
         }
        
     }
     $oItem->Codigo= $configuracion->getConsecutivo('Remision','Remision');
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
function SaveEncabezadoAjuste($datos,$modelo, $codigo){
  
    $ajuste['Identificacion_Funcionario']=$modelo['Identificacion_Funcionario'];
    $ajuste['Estado_Entrada_Bodega']= 'Aprobada';
    $ajuste['Id_Clase_Ajuste_Individual']=5;
    $ajuste['Fecha']=date("Y-m-d H:i:s");
    $ajuste['Tipo']= 'Entrada';
    $ajuste['Estado']= 'Activo';
    $ajuste['Origen_Destino']= $datos['Tipo_Origen']=='Punto_Dispensacion' ? 'Punto' : 'Bodega';
    $ajuste['Id_Origen_Destino']= $datos['Id_Origen'];


    $oItem = new complex("Ajuste_Individual","Id_Ajuste_Individual");
    foreach($ajuste as $index=>$value) {
         if($value!=''){            
            $oItem->$index=$value; 
         }
        
     }
     $oItem->Codigo= $codigo;
     $oItem->save();
     $id_ajuste = $oItem->getId();
     unset($oItem);
     
    return $id_ajuste;

 }

 function GetCodigoAjuste(){
    global $queryObj;
    $query = "SELECT ifnull(MAX(CAST(REPLACE(D.Codigo, 'DR', '') AS DECIMAL) ), 0)+1 AS Consecutivo
    FROM Ajuste_Individual D WHERE D.Codigo LIKE 'DR%'";
    $queryObj->SetQuery($query);
    $rem = $queryObj->ExecuteQuery('simple');
    
    return "DR$rem[Consecutivo]";
}

function SaveProductosAjuste($id_ajuste,$productos){
    global $codigo, $modelo;
   
    foreach ($productos as $producto) {

        $oItem=new complex('Producto_Remision','Id_Producto_Remision',$producto['Id_Producto_Remision']);
        $remision_anterior= $oItem->Id_Remision;
        unset($oItem); 

        $codRem = GetCodigoRem($remision_anterior);
        $oItem=new complex('Producto_Ajuste_Individual',"Id_Producto_Ajuste_Individual");
        $p=$producto;
        unset($p['Id_Inventario_Nuevo']);
        $p['Id_Ajuste_Individual']=$id_ajuste;
        $p['Costo']= $p['Precio'];
        $p['Observaciones'] = "Se devuelven los productos por no Conformes de la rem $codRem" ;
        unset($p['Id_Producto_Remision']);
        foreach($p as $index=>$value) {
            $oItem->$index=$value;
        }
        $oItem->Costo=number_format($p['Costo'],2,".","");
       
        $oItem->save();
        unset($oItem);   
        
        //Actualizar la cantidad reenvia en el producto no conforme
        $oItem=new complex('Producto_No_Conforme_Remision','Id_Producto_No_Conforme_Remision',$producto['Id_Producto_No_Conforme_Remision']);
        $oItem->Cantidad_Reenviada=$producto['Cantidad'];
        $oItem->save();
        unset($oItem); 
    }

    $oItem = new complex('Actividad_Ajuste_Individual',"Id_Actividad_Ajuste_Individual");
    $oItem->Id_Ajuste_Individual = $id_ajuste;
    $oItem->Identificacion_Funcionario = $modelo["Identificacion_Funcionario"];
    $oItem->Detalle = "Se creo la entrada del ajuste individual $codigo por faltantes de la remision $codRem";
    $oItem->Fecha_Creacion = date("Y-m-d H:i:s");
    $oItem->Estado = "Creacion";
    $oItem->save();
    unset($oItem);

    $oItem = new complex('Actividad_Remision',"Id_Actividad_Remision");
    $oItem->Id_Remision=$remision_anterior;
    $oItem->Identificacion_Funcionario=$modelo["Identificacion_Funcionario"];
    $oItem->Detalles="Se devolvieron los productos al ajuste con codigo $codigo por faltantes de la remision $codRem";
    $oItem->Fecha=date("Y-m-d H:i:s");
    $oItem->save();
    unset($oItem);

}
function SaveProductoRemision($id_remision,$productos){
      
   
    foreach ($productos as $producto) {
        $subtotal=floatval($producto['Precio']*$producto['Cantidad']);
        // echo $subtotal; exit;
        $oItem=new complex('Producto_Remision',"Id_Producto_Remision");
        $p=$producto;
        $p['Id_Remision']=$id_remision;
        unset($p['Id_Producto_Remision']);
        foreach($p as $index=>$value) {
            $oItem->$index=$value;
        }
        $oItem->Cantidad_Total=$producto['Cantidad'];
        $oItem->Subtotal=number_format($subtotal,2,".","");
        $oItem->Precio=number_format($producto['Precio'],2,".","");
       
        $oItem->save();
        unset($oItem);   
        
        //Actualizar la cantidad reenvia en el producto no conforme
        $oItem=new complex('Producto_No_Conforme_Remision','Id_Producto_No_Conforme_Remision',$producto['Id_Producto_No_Conforme_Remision']);
        $oItem->Cantidad_Reenviada=$producto['Cantidad'];
        $oItem->save();
        unset($oItem); 
        //Actualizar la cantidad reenvia en el producto no conforme
        $oItem=new complex('Producto_Remision','Id_Producto_Remision',$producto['Id_Producto_Remision']);
        $cantidad=$oItem->Cantidad;
        $final=$cantidad-$producto['Cantidad'];
        if($final<0){
            $final='0';
        }
        $oItem->Cantidad=number_format($final,0,"","");
        $remision_anterior= $oItem->Id_Remision;
        $oItem->save();
        unset($oItem); 
      
     }
 
     $codigo=GuardarActividadRemision($id_remision, GetCodigoRem($remision_anterior));
     GuardarActividadSeguimientoRemision($remision_anterior, $codigo);
}

function  GuardarActividadRemision($id_remision, $codig){
    global $modelo;

    $codigo =GetCodigoRem($id_remision);

    $oItem = new complex('Actividad_Remision',"Id_Actividad_Remision");
    $oItem->Id_Remision=$id_remision;
    $oItem->Identificacion_Funcionario=$modelo["Identificacion_Funcionario"];
    $oItem->Detalles="Se creo la remision con codigo $codigo por faltantes de la remision $codig";
    $oItem->Fecha=date("Y-m-d H:i:s");
    $oItem->save();
    unset($oItem);
    return $codigo;

 }
function  GuardarActividadSeguimientoRemision($id_remision, $codig){
    global $modelo;

    $oItem = new complex('Actividad_Remision',"Id_Actividad_Remision");
    $oItem->Id_Remision=$id_remision;
    $oItem->Identificacion_Funcionario=$modelo["Identificacion_Funcionario"];
    $oItem->Detalles="Se creo la remision con codigo $codig Por no conformidades ";
    $oItem->Fecha=date("Y-m-d H:i:s");
    $oItem->save();
    unset($oItem);
 }

 function GetCodigoRem($id_remision){
    global $queryObj;
    $query = "SELECT Codigo FROM Remision WHERE Id_Remision=$id_remision";
  

    $queryObj->SetQuery($query);
    $rem = $queryObj->ExecuteQuery('simple');

    return $rem['Codigo'];  
 }


?>





