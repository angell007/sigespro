<?php 
    
include_once('../class/class.consulta.php');

$query = 'SELECT PR.Id_Producto_Remision, PR.Id_Producto, R.Fecha
            FROM Producto_Remision PR 
            INNER JOIN Remision R ON R.Id_Remision = PR.Id_Remision
          WHERE (PR.Costo = 0 OR PR.Costo IS NULL) AND DATE( R.Fecha ) <= "2020-07-22" ' ;


$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$productos = $oCon->getData();
unset($oCon);
//var_dump($productos);exit;
foreach( $productos as $key => $prod ){
  
 
  $query = 'SELECT PAR.Precio,PAR.Id_Acta_Recepcion
	FROM Producto_Acta_Recepcion PAR
    INNER JOIN Acta_Recepcion AR ON PAR.Id_Acta_Recepcion=AR.Id_Acta_Recepcion  
	WHERE PAR.Id_Producto='.$prod['Id_Producto'].' AND AR.Tipo_Acta="Bodega" AND  (AR.Estado ="Aprobada" OR AR.Estado = "Acomodada"  ) AND DATE(AR.Fecha_Creacion)<="'.$prod['Fecha'].'" Limit 1 ' ;


  $oCon = new consulta();
  $oCon->setQuery($query);
  $precio = $oCon->getData();
  unset($oCon);

  if($precio){
     
     
 	 $query = 'UPDATE Producto_Remision SET Costo = '.$precio['Precio'].' , Costo_Actualizado = TRUE 
     			WHERE Id_Producto_Remision = '.$prod['Id_Producto_Remision'];
      $oCon = new consulta();
      $oCon->setQuery($query);
      $oCon->createData();
       echo'<br> ------<br>';
       echo 'Id:'.$prod['Id_Producto_Remision'].' -> Precio: '.$precio['Precio'].' -> Acta: '.$precio['Id_Acta_Recepcion'];
       echo'<br>-------<br>';
   	  unset($oCon);
    
  }
  
  

}


//echo json_encode($productos);

