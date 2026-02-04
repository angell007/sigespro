<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
//header('Content-Type: application/json');

require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');



$query='
SELECT PIFP.*, IFP.Id_Punto_Dispensacion
FROM Producto_Inventario_Fisico_Punto PIFP
INNER JOIN Inventario_Fisico_Punto IFP 
ON IFP.Id_Inventario_Fisico_Punto = PIFP.Id_Inventario_Fisico_Punto
WHERE PIFP.Id_Inventario_Fisico_Punto = 90
GROUP BY Id_Producto,Lote  
ORDER BY `PIFP`.`Fecha_Vencimiento`  ASC
';

$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);

$i=0;
foreach($resultado as $res){ $i++;
        echo $i.") ".$res["Id_Producto_Inventario_Fisico"]." -  &emsp;Id_Producto: ".$res["Id_Producto"]." - Conteo1: ".$res["Primer_Conteo"]." - Conteo2: ".$res["Segundo_Conteo"]." - Lote: ".$res["Lote"]." Punto ".$res['Id_Punto_Dispensacion']."<br>";

        $oItem= new complex("Inventario","Id_Inventario");
        $oItem->Id_Producto = $res["Id_Producto"];
        $oItem->Lote= $res["Lote"];
        //$oItem->Codigo = $codigo;
        //$oItem->Alternativo = $codigo;
        $oItem->Costo = 0; 
        $oItem->Fecha_Vencimiento= $res["Fecha_Vencimiento"];
        $oItem->Id_Punto_Dispensacion = $res["Id_Punto_Dispensacion"];
        $oItem->Cantidad = number_format($res["Cantidad_Final"],0,"","");
        $oItem->Actualizado = "Nuevo";
        // $oItem->save();
        $id_inv = $oItem->getId(); 
        unset($oItem);
        
        $oItem= new complex("Producto_Inventario_Fisico_Punto","Id_Producto_Inventario_Fisico_Punto",$res["Id_Producto_Inventario_Fisico"]);
        $oItem->Actualizado = "Actualiza_Nuevo_".$id_inv;
        //$oItem->save();
        unset($oItem);
}

?>