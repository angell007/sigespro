<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.http_response.php');
include_once('../../class/class.utility.php');

$http_response = new HttpResponse();
$queryObj = new QueryBaseDatos();
$util = new Utility();


$query=CrearQuery();

$queryObj->SetQuery($query);
$dispensaciones = $queryObj->ExecuteQuery('Multiple');

$productos_sin_existencias=[];

 foreach ($dispensaciones as $dis) {
   $productos_dispensacion=GetProductosDispensacion($dis['Id_Dispensacion']);

   foreach ($productos_dispensacion as $pd) {
      $lotes=GetLotes($pd['Id_Producto'],$dis['Id_Punto_Dispensacion']);

      if(count($lotes)>0){
          $cantidad=$pd['Cantidad'];
          $flag=true;
          for ($i=0; $i < count($lotes) ; $i++) { 
            if($flag && $cantidad<=$lotes[$i]['Cantidad']){
                $cantidad_inv=ActualizarProductoDispensacion($pd['Id_Dispensacion'],$pd['Id_Producto'],$cantidad,$lotes[$i]);
                DescontarInventario($cantidad_inv,$lotes[$i]);

                $flag=false;
            }elseif ($flag && $cantidad>$lotes[$i]['Cantidad']){
                $lote=$lotes[$i];
                $cantidad_inv=ActualizarProductoDispensacion($pd['Id_Dispensacion'],$pd['Id_Producto'],$lotes[$i]['Cantidad'],$lotes[$i]);
                DescontarInventario($cantidad_inv,$lotes[$i]);
                
                $cantidad=$cantidad-$lotes[$i]['Cantidad'];

            }
          }

      }else{
          $p['Id_Dispensacion']=$dis['Id_Punto_Dispensacion'];
          $p['Id_Producto']=$pd['Id_Producto'];

          array_push($productos_sin_existencias,$p);
      }
   }
  
 } 





echo json_encode($productos);



function CrearQuery(){ 
   
   $query='SELECT
   D.Id_Dispensacion,
   D.Codigo,D.Id_Punto_DIspensacion,
   SUM(PD.Cantidad_Formulada - PD.Cantidad_Entregada) as Cantidad
    FROM Producto_Dispensacion PD
    INNER JOIN Dispensacion D ON PD.Id_Dispensacion = D.Id_Dispensacion
    WHERE
    D.Codigo IN ("DIS568473",
    "DIS586191","DIS589121","DIS561951","DIS563334","DIS593284","DIS539676","DIS561958","DIS515978","DIS56174","DIS588411","DIS534238","DIS538026","DIS561965","DIS544032","DIS552419","DIS552420","DIS552421","DIS526056","DIS535235","DIS552384","DIS552386","DIS552416","DIS55218","DIS552422","DIS552423","DIS552424","DIS552425","DIS552426","DIS552427","DIS554593","DIS593339","DIS553347","DIS593353","DIS514434","DIS533907","DIS535217","DIS534184","DIS591446","DIS533442","DIS543183","DIS549315","DIS554469","DIS559662","DIS563311","DIS567100","DIS574141","DIS571797","DIS563178","DIS569734","DIS571903","DIS515959","DIS527694","DIS551021","DIS563165","DIS568442","DIS568533","DIS572680","DIS574626","DIS56335","DIS586043","DIS589248","DIS515987","DIS531208","DIS534372","DIS534549","DIS536872","DIS551590")
    group by 
   PD.Id_Dispensacion    
   having Cantidad > 0';




    return $query;
}

function GetProductosDispensacion($id_dispensacion){
    global $queryObj;

    $query="SELECT (Cantidad_Formulada-Cantidad_Entregada) as Cantidad, Id_Producto_Dispensacion,Id_Producto,Id_Dispensacion FROM Producto_Dispensacion PD WHERE Id_Dispensacion=$id_dispensacion HAVING Cantidad>0 ";
    $queryObj->SetQuery($query);
    $productos = $queryObj->ExecuteQuery('Multiple');

    return $productos;
}

function GetLotes($producto,$punto){
    global  $queryObj;
    
    $condicion_lotes="WHERE I.Cantidad>0 AND I.Id_Punto_Dispensacion=$punto";
   
   
    $query="SELECT I.Id_Inventario, I.Id_Producto,I.Lote, I.Cantidad as Cantidad,I.Fecha_Vencimiento 
    FROM Inventario I 
    ".$condicion_lotes." AND I.Id_Producto= $producto" ;


    $queryObj->SetQuery($query);
    $lotes=$queryObj->ExecuteQuery('Multiple');

    return $lotes;
   
}

function ActualizarProductoDispensacion($id_dispensacion,$id_producto,$cantidad,$lote){

    global $queryObj;

    $cantidad_inv=0;

    $query="SELECT *,(Cantidad_Formulada-Cantidad_Entregada) as Cantidad_Pendiente
    FROM Producto_Dispensacion WHERE Id_Dispensacion=$id_dispensacion AND Id_Producto=$id_producto HAVING Cantidad_Pendiente>0 " ;


    $queryObj->SetQuery($query);
    $pd=$queryObj->ExecuteQuery('simple');
    if($pd['Id_Producto_Dispensacion']){
        $id=$pd['Id_Producto_Dispensacion']; 
        
        /*Actualizo la cantidad formulada del anterior registro a la que tiene en entregada si no tiene lo que gace es que solo se cambia el lote */

        if($pd['Cantidad_Entregada']>0){
          
         $oItem=new complex ("Producto_Dispensacion","Id_Producto_Dispensacion",$pd['Id_Producto_Dispensacion']);
         $oItem->Cantidad_Formulada=$pd['Cantidad_Entregada'];
         // $oItem->save();
         unset($oItem);

         unset($pd['Id_Producto_Dispensacion']);
         $pd['Lote']=$lote['Lote'];
         if($cantidad>=$pd['Cantidad_Pendiente']){
            $pd['Cantidad_Entregada']=$cantidad;
            $cantidad_inv=$cantidad;
         }else{
            $pd['Cantidad_Entregada']=$pd['Cantidad_Pendiente'];
            $cantidad_inv=$pd['Cantidad_Pendiente'];
         }
         $pd['Cantidad_Formulada']=$pd['Cantidad_Pendiente'];

         $oItem = new complex("Producto_Dispensacion","Id_Producto_Dispensacion");
         foreach ($pd as $index => $value) {
            if($value!=''){
                $oItem->$index=$value;
            }
         }
         //$oItem->save();      
         unset($oItem);

        }else{

            if($cantidad>=$pd['Cantidad_Pendiente']){
                $pd['Cantidad_Entregada']=$cantidad;
                $cantidad_inv=$cantidad;
             }else{
                $pd['Cantidad_Entregada']=$pd['Cantidad_Pendiente'];
                $cantidad_inv=$pd['Cantidad_Pendiente'];
             }

            $oItem=new complex ("Producto_Dispensacion","Id_Producto_Dispensacion",$pd['Producto_Dispensacion']);
            $oItem->Cantidad_Entregada=$cantidad_inv;
            $oItem->Lote=$lote['Lote'];
            // $oItem->save();
            unset($oItem);

        }


    }

return $cantidad_inv;

}

function DescontarInventario($cantidad,$lote){
    global $queryObj;

    $query="SELECT Cantidad
    FROM Inventario WHERE Id_Inventario=$lote[Id_Inventario] " ;


    $queryObj->SetQuery($query);
    $inv=$queryObj->ExecuteQuery('simple');

    if($inv['Cantidad']){

        $cantidad_Final=$inv['Cantidad']-$cantidad;
        if($cantidad_Final<0){
            $cantidad_Final=0;
        }
        $oItem=new complex ("Inventario","Id_Inventario",$lote['Id_Inventario']);
        $oItem->Cantidad=number_format($cantidad_Final,0,"","");        
        // $oItem->save();
        unset($oItem);
    }
}


?>