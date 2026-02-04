<?php 
    
include_once('../class/class.consulta.php');

$query = 'SELECT PD.Id_Producto_Dispensacion, PD.Id_Producto, D.Fecha_Actual
            FROM Producto_Dispensacion PD 
            INNER JOIN Dispensacion D ON D.Id_Dispensacion = PD.Id_Dispensacion
           WHERE  DATE( D.Fecha_Actual) <= "2020-07-22" AND PD.Costo_Actualizado != 1  ' ;



$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$productos = $oCon->getData();

unset($oCon);
foreach( $productos as $key => $prod ){
  
 
  $query = 'SELECT PAR.Precio,PAR.Id_Acta_Recepcion
	FROM Producto_Acta_Recepcion PAR
    INNER JOIN Acta_Recepcion AR ON PAR.Id_Acta_Recepcion=AR.Id_Acta_Recepcion  
	WHERE PAR.Id_Producto='.$prod['Id_Producto'].' AND AR.Tipo_Acta="Bodega" AND  (AR.Estado ="Aprobada" OR AR.Estado = "Acomodada"  ) AND   DATE(AR.Fecha_Creacion)<="'.$prod['Fecha_Actual'].'" Limit 1 ' ;


  $oCon = new consulta();
  $oCon->setQuery($query);
  $precio = $oCon->getData();
  unset($oCon);

  if($precio){
     
     
 	 $query = 'UPDATE Producto_Dispensacion SET Costo = '.$precio['Precio'].' , Costo_Actualizado = TRUE 
     			WHERE Id_Producto_Dispensacion = '.$prod['Id_Producto_Dispensacion'];
      $oCon = new consulta();
      $oCon->setQuery($query);
      $oCon->createData();
       echo'<br> ------<br>';
       echo 'Id:'.$prod['Id_Producto_Dispensacion'].' -> Precio: '.$precio['Precio'].' -> Acta: '.$precio['Id_Acta_Recepcion'];
       echo'<br>-------<br>';
   	  unset($oCon);
    
   
  
  }
  
  

}


//echo json_encode($productos);

