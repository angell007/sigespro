<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
//header('Content-Type: application/json');

require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');


$oLista = new lista("Producto_Inventario_Fisico");
$productos= $oLista->getList();
unset($oLista);

//echo count($productos);
$i=0;
$j=0;
$k=0;
foreach($productos as $prod){
    if($prod["Id_Inventario"]!=0){
        $i++;
        $oItem= new complex("Inventario","Id_Inventario",$prod["Id_Inventario"]);
        $oItem->Cantidad = number_format($prod["Segundo_Conteo"],0,"","");
       // $oItem->save();
        unset($oItem);
        
        $oItem= new complex("Producto_Inventario_Fisico","Id_Producto_Inventario_Fisico",$prod["Id_Producto_Inventario_Fisico"]);
        $oItem->Actualizado = "Actualiza_General";
        //$oItem->save();
        unset($oItem);
    }else{
       $query='SELECT * FROM Inventario WHERE Lote="'.$prod["Lote"].'" AND Id_Producto='.$prod["Id_Producto"]." AND Id_Punto_Dispensacion=0";
        $oCon= new consulta();
        $oCon->setQuery($query);
        //$oCon->setTipo('Multiple');
        $res = $oCon->getData();
        unset($oCon);
        if(isset($res["Id_Inventario"])){
            $j++;
            echo "Inventario_Encontrado: ".$prod["Id_Producto"]." - ".$prod["Lote"]." - ".$prod["Segundo_Conteo"]."<br>";
            $oItem= new complex("Inventario","Id_Inventario",$res["Id_Inventario"]);
            $oItem->Cantidad = number_format($prod["Segundo_Conteo"],0,"","");
            //$oItem->save();
            unset($oItem);
            
            $oItem= new complex("Producto_Inventario_Fisico","Id_Producto_Inventario_Fisico",$prod["Id_Producto_Inventario_Fisico"]);
            $oItem->Actualizado = "Actualiza_Con_Cero".$res["Id_Inventario"];
            //$oItem->save();
            unset($oItem);
        
        }else{
            $oItem= new complex("Inventario_Fisico","Id_Inventario_Fisico",$prod["Id_Inventario_Fisico"]);
            $inv_fisico= $oItem->getData();
            unset($oItem);
            $k++;
            $codigo=substr(hexdec(uniqid()),2,12);
            echo $k."-----Inventario No Encontrado ".$prod["Id_Producto"]."  --  ". $prod["Lote"]." - ".$prod["Primer_Conteo"]." - ".$prod["Segundo_Conteo"]."<br>";
            $oItem= new complex("Inventario","Id_Inventario");
            $oItem->Id_Producto = $prod["Id_Producto"];
            $oItem->Lote= $prod["Lote"];
            $oItem->Codigo = $codigo;
            $oItem->Alternativo = $codigo;
            $oItem->Costo = 0; 
            $oItem->Fecha_Vencimiento= $prod["Fecha_Vencimiento"];
            $oItem->Id_Bodega = $inv_fisico["Bodega"];
            $oItem->Cantidad = number_format($prod["Segundo_Conteo"],0,"","");
            $oItem->Actualizado = "Nuevo";
            //$oItem->save();
            $id_inv = $oItem->getId();
            unset($oItem);
            
            $oItem= new complex("Producto_Inventario_Fisico","Id_Producto_Inventario_Fisico",$prod["Id_Producto_Inventario_Fisico"]);
            $oItem->Actualizado = "Actualiza_Nuevo_".$id_inv;
            //$oItem->save();
            unset($oItem);
        }
        
    }
}
echo $i."<br>";
echo $j;
//$query='SELECT Count(Lote) Repe, `Id_Producto_Inventario_Fisico`, `Id_Producto` as IDP, `Id_Inventario`, `Primer_Conteo` as Cont1, `Segundo_Conteo` ,`Cantidad_Inventario`, `Lote` FROM `Producto_Inventario_Fisico` GROUP BY Lote, Id_Producto HAVING count(Lote) > 1 ORDER BY `IDP` ASC';

/*$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);
*/
//$i=0;
//echo count($resultado);
/*foreach($resultado as $res){ $i++;
    
    $query2='SELECT `Id_Producto_Inventario_Fisico`, `Id_Producto` as IDP, `Id_Inventario`, `Primer_Conteo` as Cont1, `Segundo_Conteo` , `Fecha_Segundo_Conteo`,`Cantidad_Inventario`, `Lote` FROM `Producto_Inventario_Fisico` WHERE `Id_Producto` = '.$res["IDP"].' AND `Lote` = "'.$res["Lote"].'"
        ORDER BY `Producto_Inventario_Fisico`.`Lote`  ASC';
        
        $oCon= new consulta();
        $oCon->setQuery($query2);
        $oCon->setTipo('Multiple');
        $prods = $oCon->getData();
        unset($oCon);
        
        echo count($prods)."<br>";
        
        $id_inv=0;
        foreach($prods as $pr){
            if($id_inv==$pr["Id_Inventario"]){
                echo $pr["Id_Inventario"]." : ".$pr["Cont1"]." - ".$pr["Segundo_Conteo"]."<br>";
            }
            $id_inv=$pr["Id_Inventario"];
        }*/
   // echo $i.") ".$res["Repe"]."<br>Id_Producto: ".$res["IDP"]."<br>Id_Inventario: ".$res["Id_Inventario"]."<br> Conteo1: ".$res["Cont1"]."<br> Conteo2: ".$res["Segundo_Conteo"]."<br> Lote: ".$res["Lote"]."<br> Inventario: ".$res["Cantidad_Inventario"]."<br>==========================================<br>";

 
//var_dump($res);
/*
    if($res["Cantidad_Inventario"]==$res["Cont1"]&&$res["Segundo_Conteo"]==0){
        echo $i.") &emsp;Id_Producto: ".$res["IDP"]." - Id_Inventario: ".$res["Id_Inventario"]." - Conteo1: ".$res["Cont1"]." - Conteo2: ".$res["Segundo_Conteo"]." - Lote: ".$res["Lote"]." - Inventario: ".$res["Cantidad_Inventario"]."<br>";
        $oItem = new complex("Producto_Inventario_Fisico","Id_Producto_Inventario_Fisico",$res["Id_Producto_Inventario_Fisico"]);
        $oItem->Segundo_Conteo=$res["Cont1"];
       //$oItem->save();
        unset($oItem);
        
    }
*/
//}
?>