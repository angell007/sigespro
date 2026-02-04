<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


require_once('./../config/start.inc.php');
include_once('./../class/class.lista.php');
include_once('./../class/class.complex.php');
include_once('./../class/class.consulta.php');


$oLista = new lista("Inventarios_Borrados");
$oLista->setOrder("Id","ASC");
$invs = $oLista->getList();
unset($oLista);


$i=0;
foreach($invs as $inv){ $i++;
    
    $oItem = new complex("Inventario","Id_Inventario",$inv["Id_Inventario"]);
    $inv1 = $oItem->getData();
    unset($oItem);
    
    $oItem = new complex("Inventario","Id_Inventario",$inv["Id_Inventario_Eliminado"]);
    $inv2 = $oItem->getData();
    unset($oItem);
    
    if(isset($inv1["Id_Inventario"])){
        $query="UPDATE Producto_Factura_Venta SET Id_Inventario=".$inv["Id_Inventario"]." WHERE Id_Inventario=".$inv['Id_Inventario_Eliminado'];
        echo "Actualiza bien: ". $query."<br>";
        $oCon= new consulta();
        $oCon->setQuery($query);
        $oCon->createData();     
        unset($oCon);
    }elseif(isset($inv2["Id_Inventario"])){
        $query="UPDATE Producto_Factura_Venta SET Id_Inventario=".$inv["Id_Inventario_Eliminado"]." WHERE Id_Inventario=".$inv['Id_Inventario'];
        echo "Actualiza reves: ". $query."<br>";
        $oCon= new consulta();
        $oCon->setQuery($query);
        $oCon->createData();     
        unset($oCon);
        
    }else{
        echo "No actualiza ninguno<br>";
    }
     
}


        
      

?>