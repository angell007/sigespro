<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.http_response.php');
require_once('../../class/class.configuracion.php');

$queryObj = new QueryBaseDatos();
$response = array();
$http_response = new HttpResponse();

date_default_timezone_set('America/Bogota');

$modelo = ( isset( $_REQUEST['modelo'] ) ? $_REQUEST['modelo'] : '' );
$modelo = (array) json_decode($modelo);

$grupo = ( isset( $_REQUEST['grupo'] ) ? $_REQUEST['grupo'] : '' );
$grupo = (array) json_decode($grupo,true);
$mes = isset($_REQUEST['mes']) ? $_REQUEST['mes'] : '';


$hoy=date("Y-m-t", strtotime(date('Y-m-d')));
$nuevafecha = strtotime ( '+ 1 months' , strtotime ( $hoy) ) ;
$nuevafecha= date('Y-m-t', $nuevafecha);

$nombre_antiguo=GetNombreProducto($modelo['Id_Producto_Viejo']);
$nombre_nuevo=GetNombreProducto($modelo['Id_Producto_Nuevo']);
$condicion_lotes=SetCondicionLotes();


$condicion=SetCondiciones();
$query=GetQuery();

$queryObj->SetQuery($query);
$productos = $queryObj->ExecuteQuery('Multiple');

$id_dispensacion='';
$id_productos_dispensacion_antiguos='';
$id_productos_dispensacion_nuevos='';
$cantidad=$modelo['Cantidad'];


for ($i=0; $i < count($productos); $i++) { 
     if((INT)$cantidad>0){
     
          $oItem=new complex("Producto_Dispensacion","Id_Producto_Dispensacion",$productos[$i]['Id_Producto_Dispensacion']);
         
          $producto_dis=$oItem->getData();
          $id_dispensacion.=$producto_dis['Id_Dispensacion'].",";
          $id_productos_dispensacion_antiguos.=$producto_dis['Id_Producto_Dispensacion'].",";
          if( $producto_dis['Cantidad_Entregada']=='0'){
               $id_productos_dispensacion_nuevos.= $producto_dis['Id_Producto_Dispensacion'].",";
               $oItem->Codigo_Cum=GetCum($modelo['Id_Producto_Nuevo']);
               $oItem->Id_Producto=$modelo['Id_Producto_Nuevo'];
               $oItem->save();
               unset($oItem);

          }else{
               $oItem->Cantidad_Formulada=$productos[$i]['Cantidad_Entregada'];
              
               $id_dispensacion.=$producto_dis['Id_Dispensacion'].",";
               $cantidad=$cantidad-$productos[$i]['Cantidad_Requerida'];
               $oItem->save();
               unset($oItem);
     
               unset($producto_dis['Id_Producto_Dispensacion']);
     
               $producto_dis['Id_Producto']=$modelo['Id_Producto_Nuevo'];
               $producto_dis['Codigo_Cum']=GetCum($modelo['Id_Producto_Nuevo']);
               $producto_dis['Id_Inventario_Nuevo']='0';
               $producto_dis['Lote']='Pendiente';
               $producto_dis['Cantidad_Formulada']=$productos[$i]['Cantidad_Requerida'];
               $producto_dis['Cantidad_Entregada']='0';
               $producto_dis['Entregar_Faltante']='0';
     
               $oItem = new complex("Producto_Dispensacion","Id_Producto_Dispensacion");
               foreach($producto_dis as $index=>$value) {
                     $oItem->$index=$value;
               }
               $oItem->save();
               $id_productos_dispensacion_nuevos.= $oItem->getId().",";  
               unset($oItem);

          }
          RegistrarActividadCambio($producto_dis['Id_Dispensacion']);

          
     }else{
          break;
     }
}

$id_productos_dispensacion_nuevos=trim($id_productos_dispensacion_nuevos,',');
$id_productos_dispensacion_antiguos=trim($id_productos_dispensacion_antiguos,',');
$id_dispensacion=trim($id_dispensacion,',');

$oItem = new complex("Cambio_Producto","Id_Cambio_Producto");
$oItem->Id_Producto_Inicial=$modelo['Id_Producto_Viejo'];
$oItem->Id_Producto_Final=$modelo['Id_Producto_Nuevo'];
$oItem->Id_Producto_Dispensacion_Viejo=$id_productos_dispensacion_antiguos;
$oItem->Id_Dispensacion=$id_dispensacion;
$oItem->Identificacion_Funcionario=$modelo['funcionario'];
$oItem->Fecha=date("Y-m-d H:i:s");
$oItem->Id_Producto_Dispensacion_Nuevo=$id_productos_dispensacion_nuevos;
$oItem->save();
$id_registro= $oItem->getId();
unset($oItem);



$query=GetQueryProducto($modelo['Id_Producto_Nuevo']);
$queryObj->SetQuery($query);
$producto = $queryObj->ExecuteQuery('simple');

$lotes=GetLotes($producto); 
/* var_dump($lotes);
exit; */
if (count($lotes)>0) {             
     $cantidad_presentacion=$producto['Cantidad_Presentacion'];
     $cantidad=$modelo['Cantidad'];
     $producto['Cantidad_Requerida']=$modelo['Cantidad'];
   /*   var_dump($producto['Cantidad_Requerida']);
     exit; */
     $modulocantidad=$cantidad%$cantidad_presentacion;
     if($modulocantidad!=0){
         $cantidad=$cantidad+($cantidad_presentacion-$modulocantidad);
         $producto['Cantidad_Requerida']=$cantidad; 
        
     }
     

     $cantidad_inicial= $producto['Cantidad_Requerida'];
     $producto['Lotes']=$lotes;


     $multiplo=0;
     $cantidad_presentacion_producto=false;

     if($grupo['Presentacion']=='Si'){
         $multiplo=$cantidad%$cantidad_presentacion;
         $cantidad_presentacion_producto=true;

     }

     $lotes_seleccionados=[];
     $lotes_visuales=[];

     if($multiplo==0 && $cantidad>0){
         $flag=true;

         for ($i=0; $i <count($lotes) ; $i++) { 
            
             if($flag && $cantidad<=$lotes[$i]['Cantidad']){
                 $lote=$lotes[$i];
                 $lote['Cantidad_Seleccionada']=$cantidad;

                #metodo de seleccionar los lotes
                SelecionarLotes($lote);

                 $lotes[$i]['Cantidad_Seleccionda']=$cantidad;
                 $labellote="Lote: ".$lotes[$i]['Lote']." - Vencimiento: ".$lotes[$i]['Fecha_Vencimiento']." - Cantidad: ".$cantidad;
                

                 $producto['Cantidad']=$cantidad_inicial;

                 array_push($lotes_visuales,$labellote);
                 array_push($lotes_seleccionados,$lote);
                 $flag=false;
             }elseif ($flag && $cantidad>$lotes[$i]['Cantidad']){
                 $lote=$lotes[$i];
                 $lote['Cantidad_Seleccionada']=$lotes[$i]['Cantidad'];
                
                #metodo de seleccionar los lotes
                SelecionarLotes($lote); 
                 array_push($lotes_seleccionados,$lote);

                 $labellote="Lote: ".$lotes[$i]['Lote']." - Vencimiento: ".$lotes[$i]['Fecha_Vencimiento']." - Cantidad: ".$lotes[$i]['Cantidad'];

                 $producto['Cantidad']=$producto['Cantidad']+$lotes[$i]['Cantidad'];

                 $cantidad=$cantidad-(INT)$lotes[$i]['Cantidad'];

                 if($cantidad_presentacion_producto){
                     $modulo=$cantidad%$cantidad_presentacion;
                     if($modulo!=0){
                         $producto['Cantidad_Requerida']=$producto['Cantidad_Requerida']+($cantidad_presentacion-$modulo);
                         $cantidad=$cantidad+($cantidad_presentacion-$modulo);                                
                     }
                 }
                 array_push($lotes_visuales,$labellote);

             }

         }

         $producto['Lotes_Visuales']=$lotes_visuales;
         $producto['Lotes_Seleccionados']=$lotes_seleccionados;
     }
 }
$http_response->SetRespuesta(0, 'Operacion Exitosa', 'Se ha guardado cambiado correctamente el cambio de producto!');
$response = $http_response->GetRespuesta();

$response['producto']=$producto;
$response['id_cambio']=$id_registro;

echo json_encode($response);

function SetCondiciones(){
     global $modelo;
 
     $condicion='';
 
 $condicion.=" WHERE D.Id_Punto_Dispensacion = $modelo[id_destino] AND DATE(D.Fecha_Actual) BETWEEN '$modelo[fini]'
  AND '$modelo[ffin]'
  AND PR.Id_Producto=$modelo[Id_Producto_Viejo] ";
 
 if($modelo['eps']!=''){
     $condicion.=" AND PA.Nit='$modelo[eps]' ";
 }
   return $condicion;
     
 }

 function GetQuery(){
     global $condicion; 

     $query='SELECT
          PR.Id_Producto_Dispensacion, 
         
          PR.Id_Producto, 
         
         (PR.Cantidad_Formulada-PR.Cantidad_Entregada) as Cantidad_Requerida,

         PR.Cantidad_Formulada, PR.Cantidad_Entregada
     
         FROM Producto_Dispensacion PR
         INNER JOIN (SELECT Id_Dispensacion, Numero_Documento, Fecha_Actual,Id_Punto_Dispensacion  FROM Dispensacion WHERE Estado_Dispensacion!="Anulada"   ) D ON PR.Id_Dispensacion=D.Id_Dispensacion
         INNER JOIN (SELECT Id_Paciente, EPS,Nit  FROM Paciente   ) PA ON D.Numero_Documento=PA.Id_Paciente 

         '.$condicion.' 
         
         HAVING Cantidad_Requerida >0 ';

         return $query;
 }

 function GetCum($id_producto){
     global $queryObj;

     $query="SELECT Codigo_Cum FROM Producto WHERE Id_Producto=$id_producto";
     $queryObj->SetQuery($query);
     $cum = $queryObj->ExecuteQuery('simple');

     return $cum['Codigo_Cum'];
 }

 function GetQueryProducto($id_producto){
   global $modelo,$grupo;
    $cond = '    
    INNER JOIN Estiba E ON I.Id_Estiba=E.Id_Estiba
    INNER JOIN Bodega_Nuevo B ON E.Id_Bodega_Nuevo = B.Id_Bodega_Nuevo
    INNER JOIN Grupo_Estiba G ON G.Id_Grupo_Estiba = E.Id_Grupo_Estiba
    WHERE B.Id_Bodega_Nuevo = ' . $modelo['id_origen'] . ' AND  G.Id_Grupo_Estiba = ' . $grupo['Id_Grupo'] .' AND PRD.Id_Producto = '.$id_producto;

    $query='SELECT SubC.Nombre as Subcategoria,
     SubC.Separable AS Categoria_Separable,PRD.Id_Subcategoria,
      PRD.Id_Producto,IFNULL((SELECT Costo_Promedio  FROM Costo_Promedio WHERE Id_Producto = PRD.Id_Producto),"0") as Precio, PRD.Embalaje,SUM(I.Cantidad-(I.Cantidad_Apartada+I.Cantidad_Seleccionada)) as Cantidad_Disponible, CONCAT(PRD.Principio_Activo," ",PRD.Presentacion," ",PRD.Concentracion," ", PRD.Cantidad," ", PRD.Unidad_Medida) as Nombre,  PRD.Nombre_Comercial, PRD.Laboratorio_Comercial,PRD.Laboratorio_Generico,PRD.Codigo_Cum, PRD.Cantidad_Presentacion, 0 as Seleccionado, 0 as Cantidad, (
          CASE
          WHEN PRD.Gravado="Si" THEN (SELECT Valor FROM Impuesto WHERE Valor>0 ORDER BY Id_Impuesto DESC LIMIT 1)
          WHEN PRD.Gravado="No"  THEN 0
        END
      ) as Impuesto, IFNULL((SELECT Costo_Promedio  FROM Costo_Promedio WHERE Id_Producto = PRD.Id_Producto),"0") as Costo 
      FROM Inventario_Nuevo I
      INNER JOIN Producto PRD
      On I.Id_Producto=PRD.Id_Producto
      INNER JOIN Subcategoria SubC ON PRD.Id_Subcategoria = SubC.Id_Subcategoria'.$cond;
     
       
      return $query;
 }


 function GetLotes($producto){
     global  $queryObj,$condicion_lotes;
     $condicionBodega = ' 
     INNER JOIN Producto PRD
     On I.Id_Producto=PRD.Id_Producto
     INNER JOIN Subcategoria SubC ON PRD.Id_Subcategoria = SubC.Id_Subcategoria ';


     $having="  HAVING Cantidad>0 ORDER BY I.Fecha_Vencimiento ASC";
    
    
     $query1="SELECT I.Id_Inventario_Nuevo, I.Id_Producto,I.Lote,(I.Cantidad-(I.Cantidad_Apartada+I.Cantidad_Seleccionada)) as Cantidad,
     I.Fecha_Vencimiento,$producto[Precio] as Precio, 0 as Cantidad_Seleccionada FROM Inventario_Nuevo I 
     $condicionBodega $condicion_lotes AND  I.Id_Producto= $producto[Id_Producto] ". $having ;
 
 
     $queryObj->SetQuery($query1);
     $lotes=$queryObj->ExecuteQuery('Multiple');
 
     return $lotes;
    
 }

 function SetCondicionLotes(){
#enviar grupo
     global $modelo,$nuevafecha,$grupo,$mes;
     $condicion_principal = '
     INNER JOIN Estiba E ON I.Id_Estiba=E.Id_Estiba
     INNER JOIN Bodega_Nuevo B ON E.Id_Bodega_Nuevo = B.Id_Bodega_Nuevo
     INNER JOIN Grupo_Estiba G ON G.Id_Grupo_Estiba = E.Id_Grupo_Estiba
     WHERE B.Id_Bodega_Nuevo = ' . $modelo['id_origen'] . ' AND  G.Id_Grupo_Estiba = ' . $grupo['Id_Grupo'];
  /*    $condicion_principal=" WHERE Id_Bodega=$modelo[id_origen] "; */
     
     if( $grupo['Fecha_Vencimiento'] === "Si"  && $mes != '-1'){
         $condicion_principal.=" AND I.Fecha_Vencimiento >= '$nuevafecha' ";
     }

     return $condicion_principal;
 
 }

 function SelecionarLotes($lote){
    global $queryObj;

    $query="SELECT Cantidad_Seleccionada FROM Inventario_Nuevo WHERE Id_Inventario_Nuevo =$lote[Id_Inventario_Nuevo]";
    $queryObj->SetQuery($query);
    $cantidad_seleccionada_inventario = $queryObj->ExecuteQuery('simple');
    $cantidad_total = $lote['Cantidad_Seleccionada'] + $cantidad_seleccionada_inventario['Cantidad_Seleccionada'];

    $oItem=new complex ("Inventario_Nuevo","Id_Inventario_Nuevo",$lote['Id_Inventario_Nuevo']);
    $oItem->Cantidad_Seleccionada=number_format($cantidad_total,0,"","");
    $oItem->save();
    unset($oItem);


}

function GetNombreProducto($id){
    global $queryObj;

    $query="SELECT Nombre_Comercial FROM Producto WHERE Id_Producto =$id";
    $queryObj->SetQuery($query);
    $nom = $queryObj->ExecuteQuery('simple');
    return $nom['Nombre_Comercial'];
}

function RegistrarActividadCambio($id){
    global $nombre_antiguo, $nombre_nuevo, $modelo;

    $ActividadDis['Fecha'] = date("Y-m-d H:i:s");
    $ActividadDis["Id_Dispensacion"] = $id;
    $ActividadDis["Identificacion_Funcionario"] = $modelo['funcionario'];

    $ActividadDis["Detalle"] = "Se realizo el cambio de producto ".$nombre_antiguo." por ".$nombre_nuevo ;
    $ActividadDis["Estado"] = "Creado";
    
    $oItem = new complex("Actividades_Dispensacion","Id_Actividades_Dispensacion");
    foreach($ActividadDis as $index=>$value) {
        $oItem->$index=$value;
    }
    $oItem->save();
    unset($oItem);
}
?>





