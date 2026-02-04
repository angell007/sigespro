<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
//header('Content-Type: application/json');

require_once('../../config/start.inc.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');

$query = 'SELECT COUNT(AR.Id_Remision) as Total, GROUP_CONCAT(Id_Acta_Recepcion_Remision) as Id_Acta FROM Acta_Recepcion_Remision AR  GROUP BY AR.Id_Remision   HAVING COUNT(AR.Id_Remision)>1 ORDER BY AR.Id_Acta_Recepcion_Remision DESC' ;

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$productos = $oCon->getData();
unset($oCon);
$id=[];
foreach ($productos as $item) {
     $id_inventario=explode(",",$item['Id_Acta']);
    $contador=$item['Total']-1;

     for ($i=0; $i <  count($id_inventario) ; $i++) { 
        if ($contador==$i){
           /* $oItem = new complex('Inventario','Id_Inventario',$id_inventario[$i]);
            $oItem->Cantidad = number_format($item['Cantidad'],0,"","");      
            $oItem->save();            
            unset($oItem);
            echo $id_inventario[$i]."-- Id_Inventario -- Cantidad-inicial ".$item['Cant']." Cantidad Final ".$item['Cantidad']."--LOTE--".$item['Lote']."<br>";*/

        }else {
           $id[]=$id_inventario[$i];
        }
     }
   
}
$id=(implode(",", $id));