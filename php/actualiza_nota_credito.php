<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');


$query = "SELECT * FROM Movimiento_Contable 
WHERE Id_Plan_Cuenta IN (830,828,121,119) AND Numero_Comprobante LIKE '%NC00%'
ORDER BY Numero_Comprobante";


$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$comprobantes = $oCon->getData();
unset($oCon);


foreach($comprobantes as $comp){
    $oItem = new complex('Movimiento_Contable','Id_Movimiento_Contable',$comp["Id_Movimiento_Contable"]);
    if($comp["Id_Plan_Cuenta"]=='119'||$comp["Id_Plan_Cuenta"]=='121'){
        if($comp["Haber"]!="0.00"&&$comp["Haber_Niif"]!="0.00"){
            echo "Esta al reves 119/1121: ".$comp["Numero_Comprobante"]." - ".$comp["Haber_Niif"]."<br>";
            $oItem->Debe=       $comp["Haber"];
            $oItem->Debe_Niif=  $comp["Haber_Niif"];
            $oItem->Haber=      "0.00";
            $oItem->Haber_Niif= "0.00";
        }
    }elseif($comp["Id_Plan_Cuenta"]=='828'||$comp["Id_Plan_Cuenta"]=='830'){
        if($comp["Debe"]!="0.00"&&$comp["Debe_Niif"]!="0.00"){
            echo "Esta al reves 828/830: ".$comp["Numero_Comprobante"]." - ".$comp["Debe_Niif"]."<br>";
            $oItem->Haber=       $comp["Debe"];
            $oItem->Haber_Niif=  $comp["Debe_Niif"];
            $oItem->Debe=      "0.00";
            $oItem->Debe_Niif= "0.00";
        }
    }
    //echo $comp["Id_Movimiento_Contable"];
    $oItem->save();
    unset($oItem);  
    
}


