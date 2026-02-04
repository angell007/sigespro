<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


require_once('./../config/start.inc.php');
include_once('./../class/class.lista.php');
include_once('./../class/class.complex.php');
include_once('./../class/class.consulta.php');

$query = 'SELECT Count(Lote) as Conteo, Lote, Id_Producto, Fecha_Vencimiento, Id_Bodega
FROM Inventario 
WHERE Id_Bodega!=0
GROUP BY Id_Producto, Lote, Id_Bodega
HAVING Conteo > 1
ORDER BY Conteo DESC, Id_Punto_Dispensacion ASC';
$oCon= new consulta();
$oCon->setTipo('Multiple');
$oCon->setQuery($query);
$lotes = $oCon->getData();
unset($oCon);


$i=0;
foreach($lotes as $lote){ $i++;
   echo $i.")  ".$lote["Conteo"]." - ".$lote["Lote"]." - ".$lote["Id_Producto"]." - ".$lote["Id_Bodega"]."<br>"; 
   
        $query = 'SELECT Id_Inventario, Cantidad, Lote, Fecha_Vencimiento,Id_Bodega
            FROM Inventario WHERE Id_Producto = '.$lote["Id_Producto"].' AND Lote LIKE "%'.trim($lote["Lote"]).'%" AND Id_Bodega = '.$lote["Id_Bodega"].' ORDER BY Fecha_Vencimiento ASC';
        $oCon= new consulta();
        $oCon->setTipo('Multiple');
        $oCon->setQuery($query);
        $inventarios = $oCon->getData();
        unset($oCon);
        
        $j=0;
        echo "<pre>";
        var_dump($inventarios);
        echo "</pre>";
        echo "<br>";
        
       if(count($inventarios)>1){
      
            $query="UPDATE Producto_Remision SET Id_Inventario=".$inventarios[1]['Id_Inventario']." WHERE Id_Inventario=".$inventarios[0]['Id_Inventario'];
            // echo $query."<br>";
            $oCon= new consulta();
            $oCon->setQuery($query);
            //$oCon->createData();     
            unset($oCon); 

            $oItem=new complex('Inventario','Id_Inventario',$inventarios[1]['Id_Inventario']);
            $cantidad=(INT)$inventarios[0]['Cantidad']+(INT)$inventarios[1]['Cantidad'];
            $oItem->Cantidad=number_format($cantidad,0,"",""); 
            //$oItem->save();
            unset($oItem);

            $oItem=new complex('Inventarios_Borrados','Id');
            $oItem->Id_Inventario_Eliminado=$inventarios[0]['Id_Inventario'];
            $oItem->Id_Inventario=$inventarios[1]['Id_Inventario'];
            //$oItem->save();
            unset($oItem);

            $oItem=new complex('Inventario','Id_Inventario',$inventarios[1]['Id_Inventario']);
            //$oItem->delete();
            unset($oItem);

            echo " --Actulaiza el inventario : ".$inventarios[0]['Id_Inventario']." - con cantidad inicial  ".$inventarios[0]['Cantidad']." y le suma ".$inventarios[1]['Cantidad']." queda un cantidad final de : ".$cantidad."---  Se elimina el inventario ".$inventarios[1]['Id_Inventario']."<br>";
        } 
        
      /*   if(count($inventarios)==2){
            echo " --".$inv["Id_Inventario"]." - ".$inv["Cantidad"]." - (".$detalle["Conteo"].") -- Lote: ".$inv['Lote']."-- Fecha Vencimiento".$inv['Fecha_Vencimiento']."<br>";
        }*/
        /*foreach ($inventarios as  $inv) {
            $query = 'SELECT COUNT(*) as Conteo FROM `Producto_Dispensacion` WHERE `Id_Inventario` = '.$inv["Id_Inventario"].'';
            $oCon= new consulta();
            $oCon->setQuery($query);
            $detalle = $oCon->getData();
            unset($oCon);
            
            $query = 'SELECT COUNT(*) as Conteo FROM `Producto_Remision` WHERE `Id_Inventario` = '.$inv["Id_Inventario"].'';
            $oCon= new consulta();
            $oCon->setQuery($query); 
            $detalle2 = $oCon->getData();
            unset($oCon);
            
      echo " --".$inv["Id_Inventario"]." - ".$inv["Cantidad"]." - Dis(".$detalle["Conteo"].") - Rem(".$detalle2["Conteo"].") -- Lote: ".$inv['Lote']."-- Fecha Vencimiento".$inv['Fecha_Vencimiento']."<br>"; 
        } */
       
            
            
        
   
}

?>