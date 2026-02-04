<?php
  header('Access-Control-Allow-Origin: *');
  header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
  date_default_timezone_set('America/Bogota');
  include_once('../../class/class.http_response.php');
  include_once('../../class/class.querybasedatos.php');
  require_once('../../class/class.configuracion.php');
  //include_once('../../class/class.contabilizar.php');
  require_once('../../class/class.qr.php'); 
  require_once('../../config/start.inc.php');

   //$contabilizar = new Contabilizar();
    $queryObj = new QueryBaseDatos();
    $http_response = new HttpResponse();
    $response = array();


     $query='SELECT 
     COUNT(*) AS Total,
     SUM(PR.Cantidad) AS Cantidad_Productos,
     PR.Cantidad_Total,
     PR.Id_Producto,
     R.Id_Remision,
     PR.Id_Inventario,
 PR.Cantidad,
 GROUP_CONCAT(distinct PR.Id_Producto_Remision) as Id_Producto_Remision
 FROM
     Producto_Remision PR
         INNER JOIN
     Remision R ON PR.Id_Remision = R.Id_Remision
     WHERE DATE(R.Fecha)=current_date() 
 GROUP BY Id_Producto , Id_Remision
 HAVING Total = 1 AND Cantidad_Productos > Cantidad_Total  ';                
    $queryObj->SetQuery($query);  
    $productos = $queryObj->ExecuteQuery('multiple');  


    foreach ($productos as  $value) {
    
      echo "Cambia en producta remision la cantidad ".$value['Cantidad']." por la siguiente cantidad ".$value['Cantidad_Total']." Id_Producto_Remision   ".$value['Id_Producto_Remision']."<br>";
      $oItem=new complex('Producto_Remision',"Id_Producto_Remision",$value['Id_Producto_Remision']);
      $oItem->Cantidad=$value['Cantidad_Total'];

   
     $oItem->save();
      unset($oItem);



      $query="SELECT Cantidad_Apartada FROM Inventario WHERE  Id_Inventario =".$value['Id_Inventario'];
      $queryObj->SetQuery($query);  
      $cantidad_apartada = $queryObj->ExecuteQuery('simple'); 

      echo "Cantidad aparatada en inventario  ".$cantidad_apartada['Cantidad_Apartada']."<br>";

      $total=$cantidad_apartada['Cantidad_Apartada']-$value['Cantidad']+$value['Cantidad_Total'];

      if($total<0){
        $total=0;
      }
      echo "Cambia en inventario  cantidad ".$cantidad_apartada['Cantidad_Apartada']." por la siguiente cantidad ".$total."<br>";
      $oItem=new complex('Inventario',"Id_Inventario",$value['Id_Inventario']);
      $oItem->Cantidad_Apartada=number_format($total,0,"","");
      $oItem->save();
      unset($oItem);
     
    }

  
  

    echo "Termino";
    
        
   

    


?>