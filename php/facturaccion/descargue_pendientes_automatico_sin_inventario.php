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
         
            
        $cantidad_inv=ActualizarProductoDispensacion($pd['Id_Dispensacion'],$pd['Id_Producto'],$pd['Cantidad'],$lotes[0]);
              

      }else{
          $p['Id_Dispensacion']=$dis['Id_Dispensacion'];
          $p['Id_Producto']=$pd['Id_Producto'];

          array_push($productos_sin_existencias,$p);
      }
   }
  
 } 
echo "________________________";
 var_dump($productos_sin_existencias);








function CrearQuery(){ 
   
   $query='SELECT
   D.Id_Dispensacion,
   D.Codigo,D.Id_Punto_Dispensacion,
   SUM(PD.Cantidad_Formulada - PD.Cantidad_Entregada) as Cantidad
    FROM Producto_Dispensacion PD
    INNER JOIN Dispensacion D ON PD.Id_Dispensacion = D.Id_Dispensacion
    WHERE
    D.Codigo IN ("DIS435394","DIS441658","DIS463722","DIS463740","DIS465319","DIS470246","DIS472721","DIS472788","DIS472812","DIS472843","DIS473531","DIS473581","DIS491106","DIS491125","DIS491178","DIS491225","DIS491269","DIS491283","DIS491294","DIS491427","DIS491454","DIS491472","DIS491511","DIS491531","DIS491540","DIS491555","DIS491611","DIS491760","DIS491775","DIS491843","DIS491852","DIS491861","DIS491902","DIS491918","DIS491929","DIS491942","DIS491978","DIS491987","DIS492102","DIS492183","DIS492193","DIS492204","DIS492229","DIS492240","DIS492304","DIS492358","DIS492397","DIS492406","DIS492434","DIS492440","DIS492458","DIS492467","DIS492472","DIS492481","DIS492495","DIS492500","DIS492514","DIS492529","DIS492561","DIS492574","DIS502114","DIS502122","DIS509278","DIS509286","DIS509356","DIS509366","DIS509415","DIS509446","DIS509474","DIS509494","DIS509521","DIS509545","DIS509637","DIS509707","DIS509729","DIS509750","DIS509782","DIS509848","DIS509911","DIS510018","DIS510072","DIS510492","DIS510513","DIS510541","DIS516878","DIS516893","DIS516931","DIS517139","DIS517165","DIS517350","DIS518444","DIS518495","DIS518838","DIS524476","DIS525174","DIS525191","DIS525199","DIS525256","DIS525271","DIS525441","DIS525474","DIS525527","DIS525842","DIS525861","DIS526053","DIS526100","DIS526109","DIS526289","DIS526294","DIS526317","DIS526333","DIS526356","DIS526357","DIS526364","DIS526368","DIS526388","DIS556320","DIS563022","DIS564616","DIS564651","DIS564751","DIS564842","DIS572034","DIS572076","DIS572191","DIS572286","DIS572332","DIS572474","DIS572514","DIS575981","DIS576089","DIS577560","DIS577830","DIS577891","DIS577962","DIS578197","DIS580121","DIS580760","DIS580783","DIS580802","DIS580914","DIS583363")  
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
    
    $condicion_lotes="WHERE  I.Id_Bodega!=0";
   
   
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

            echo "CAntidad entregada cambiada Dispensacion ".$id_dispensacion." ".$pd['Cantidad_Entregada'].'<br>';
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
            echo "Nuevo Registro Cantidad_Formulada ".$pd['Cantidad_Formulada']." --  ".$pd['Cantidad_Pendiente'].'<br>';
        // $oItem->save();      
            unset($oItem);
  
        }else{

            if($cantidad>=$pd['Cantidad_Pendiente']){
                $pd['Cantidad_Entregada']=$cantidad;
                $cantidad_inv=$cantidad;
             }else{
                $pd['Cantidad_Entregada']=$pd['Cantidad_Pendiente'];
                $cantidad_inv=$pd['Cantidad_Pendiente'];
             }
   
            $oItem=new complex ("Producto_Dispensacion","Id_Producto_Dispensacion",$pd['Id_Producto_Dispensacion']);
            $oItem->Cantidad_Entregada=$pd['Cantidad_Formulada'];
            $oItem->Lote=$lote['Lote'];
            echo "Solo Cambia Lote <br>";
           //$oItem->save();
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