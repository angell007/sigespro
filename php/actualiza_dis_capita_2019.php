<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
//header('Content-Type: application/json');

require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');

//SELECT * FROM Dispensacion D WHERE DATE(D.Fecha_Actual) BETWEEN "2019-01-01" AND "2019-12-31" AND D.Id_Tipo_Servicio = 7 AND (D.Id_Factura IS NULL OR D.Id_Factura = '')



$query='SELECT D.*, P.Nit as Cliente FROM Dispensacion D INNER JOIN Paciente P ON P.Id_Paciente = D.Numero_Documento WHERE DATE(D.Fecha_Actual) BETWEEN "2019-01-01" AND "2019-12-31" AND D.Id_Tipo_Servicio = 7 AND (D.Id_Factura IS NULL OR D.Id_Factura ="") AND D.Estado_Dispensacion!="Anulada" ';

$oCon= new consulta(); 
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$resultado = $oCon->getData();
unset($oCon);


$o=0;
foreach($resultado as $item){ $o++;

    echo $o." - ".$item["Codigo"]."<br>";
    
    if($item["Cliente"]!=""){
        $query='SELECT Id_Factura_Capita FROM Factura_Capita FC WHERE FC.Id_Cliente='.$item["Cliente"].' AND FC.Mes = "'.date("Y-m",strtotime($item["Fecha_Actual"])).'"';
        $oCon= new consulta();
        $oCon->setQuery($query);
        $factura = $oCon->getData();
        unset($oCon);
    
        if($factura&&$factura["Id_Factura_Capita"]){
            $oItem = new complex("Dispensacion","Id_Dispensacion",$item["Id_Dispensacion"]);
            $oItem->Estado_Facturacion = 'Facturada';
            $oItem->Id_Factura = $factura["Id_Factura_Capita"];
            $oItem->Fecha_Facturado = "2019-12-31";
            //$oItem->save();  
            unset($oItem); 
        }else{
            echo "No existe factura del mes ".date("Y-m",strtotime($item["Fecha_Actual"]))." con el cliente ".$item["Cliente"]."<br>";  
        }
    }else{
        echo "No trae el Cliente<br>";
    }
    
    
    
   /* $query='UPDATE Dispensacion SET Estado_Facturacion = "Sin Facturar", Id_Factura = NULL, Fecha_Facturado = NULL WHERE Id_Dispensacion = '.$item["Id_Dispensacion"];
    $oCon= new consulta();
    $oCon->setQuery($query);
    $oCon->createData();
    unset($oCon);
    */
    
    $oItem = new complex("Dispensacion","Id_Dispensacion",$item["Id_Dispensacion"]);
    $oItem->Estado_Dispensacion = "Anulada";
    //$oItem->save();  
    unset($oItem);
    
    $oItem= new complex("Actividades_Dispensacion","Id_Actividades_Dispensacion");
    $oItem->Id_Funcionario = "12345";
    $oItem->Id_Dispensacion = $item["Id_Dispensacion"];
    $oItem->Detalle = "Se anula por solicitud de contabilidad y John Bacareo, por que el paciente esta mal digitado las EPS";
    $oItem->Estado = 'Anulada';
    //$oItem->save();
    unset($oItem);
    
    //$oItem = new complex("Producto_Dispensacion","Id_Producto_Dispensacion",$item["Id_Producto_Dispensacion"]);
   // $oItem->delete();  
    //unset($oItem);
}




?>