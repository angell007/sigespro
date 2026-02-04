<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
//header('Content-Type: application/json');

require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');



$query='SELECT * FROM `Producto_Inventario_Fisico_Punto` WHERE Id_Inventario_Fisico_Punto = 1';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);

$i=0;
foreach($resultado as $res){ 
    if($res["Primer_Conteo"]==$res["Segundo_Conteo"]&&$res["Cantidad_Final"]==0){ $i++;
        echo $i.") ".$res["Id_Producto_Inventario_Fisico"]." -  &emsp;Id_Producto: ".$res["Id_Producto"]." - Conteo1: ".$res["Primer_Conteo"]." - Conteo2: ".$res["Segundo_Conteo"]." - Lote: ".$res["Lote"]."<br>";
        $oItem = new complex("Producto_Inventario_Fisico_Punto","Id_Producto_Inventario_Fisico",$res["Id_Producto_Inventario_Fisico"]);
        $oItem->Cantidad_Final=$res["Segundo_Conteo"];
       // $oItem->save();
        unset($oItem);
        
    }
}
?>