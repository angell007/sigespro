<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
//header('Content-Type: application/json');

require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');



$query='SELECT I.* FROM Inventario I WHERE I.Id_Bodega IN (1,2,5) GROUP BY I.Id_Producto   ';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);

$i=0;
foreach($resultado as $res){ $i++;

        $query = 'SELECT LG.Porcentaje, LG.Id_Lista_Ganancia FROM Lista_Ganancia LG' ;
        $oCon= new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $porcentaje = $oCon->getData();
        unset($oCon);
        foreach ($porcentaje as  $value) {
             
                if($res['Codigo_CUM']){
                        $oItem = new complex('Producto_Lista_Ganancia','Id_Producto_Lista_Ganancia');
                        $precio=number_format((($res['Costo'])/((100-$value['Porcentaje'])/100)),2,'.','');
                        $oItem->Precio = $precio;
                        $oItem->Cum = $res["Codigo_CUM"];
                        $oItem->Id_Lista_Ganancia=$value['Id_Lista_Ganancia'];
                       $oItem->save();
                        unset($oItem);
                        
                }
        }
}
?>