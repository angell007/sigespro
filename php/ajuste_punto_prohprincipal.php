<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
//header('Content-Type: application/json');

require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');


/*$query='SELECT PIFP.*, IFP.Id_Punto_Dispensacion
FROM Producto_Inventario_Fisico_Punto PIFP
INNER JOIN Inventario_Fisico_Punto IFP 
ON IFP.Id_Inventario_Fisico_Punto = PIFP.Id_Inventario_Fisico_Punto
WHERE PIFP.Id_Inventario_Fisico_Punto = 112
GROUP BY Id_Producto,Lote  
ORDER BY `PIFP`.`Fecha_Vencimiento`  ASC';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);



$contador=0;
$contador2=0;
   foreach ($resultado as  $value) {
       $queryinventario='SELECT I.Id_Inventario FROM Inventario I  WHERE I.Id_Producto='.$value['Id_Producto'].' AND I.Lote="'.$value['Lote'].'" AND I.Fecha_Vencimiento="'.$value['Fecha_Vencimiento'].'" AND I.Id_Punto_Dispensacion=24';

      
       $oCon= new consulta();
       $oCon->setQuery($queryinventario);
       $inventario = $oCon->getData();
       unset($oCon);

      
       if($inventario){
        $oItem = new complex('Inventario','Id_Inventario',$inventario['Id_Inventario']);
        $oItem->Id_Producto= $value['Id_Producto'];
        $cantidad = number_format($value["Cantidad_Final"],0,"","");
     
        $oItem->Cantidad = number_format($cantidad,0,"","");
        $oItem->Lote = $value['Lote'];
        $oItem->Fecha_Carga =date("Y-m-d H:i:s"); 
        $oItem->Fecha_Vencimiento = $value['Fecha_Vencimiento'];            
     $oItem->save();
       $contador++;
        unset($oItem);
       }else{
            $oItem = new complex('Inventario','Id_Inventario');
            $oItem->Id_Producto= $value['Id_Producto'];
            $cantidad = number_format($value["Cantidad_Final"],0,"","");
            $oItem->Cantidad = number_format($cantidad,0,"","");
            $oItem->Lote = $value['Lote'];
            $oItem->Fecha_Carga =date("Y-m-d H:i:s"); 
            $oItem->Fecha_Vencimiento = $value['Fecha_Vencimiento'];            
            $oItem->Id_Bodega = 0;            
            $oItem->Id_Punto_Dispensacion = 44;            
          $oItem->save();
            unset($oItem);

            $contador2++;

       }
   }

echo "Se actualizaron ".$contador."\r\n";
echo "Se agregaron ".$contador2."\r\n";*/
?>