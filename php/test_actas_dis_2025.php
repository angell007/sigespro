<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');
require_once('../class/html2pdf.class.php');
include_once('../class/NumeroALetra.php');


include_once('../class/class.querybasedatos.php');
include_once('../class/class.http_response.php');
require_once('../class/class.configuracion.php');
include_once('../class/class.mipres.php');

/*
$folderPath = '/home/sigesproph/public_html/ARCHIVOS/DISPENSACION/ACTAS_ENTREGA_DOS';

$i=0;

if (is_dir($folderPath)) {
    
    $files = scandir($folderPath);
    foreach ($files as $file) {
        
        if ($file !== '.' && $file !== '..') {
            
            if (pathinfo($file, PATHINFO_EXTENSION) === 'pdf') {
                $i++;
                
                $name = pathinfo($file, PATHINFO_FILENAME);
                
                $oItem=new complex('Dispensacion','Codigo',$name);
                $dis = $oItem->getData();
                unset($oItem);
                $oItem=new complex('Dispensacion','Id_Dispensacion',$dis["Id_Dispensacion"]);
                $oItem->Acta_Entrega="https://sigesproph.com.co/ARCHIVOS/DISPENSACION/ACTAS_ENTREGA_DOS/".$name.".pdf";
                $oItem->save(); 
                unset($oItem);
                
                echo $i."-".$name . "<br>";

            }
        }
    }
} else {
    echo "La carpeta no existe.";
}

*/

$query = 'SELECT *
FROM A_Entrega_Panales_2025 E
WHERE E.DISPENSACION IS NOT NULL AND E.DISPENSACION !=""';
    
$oCon= new consulta();
$oCon->setQuery($query);
$oCon->setTipo("Multiple");
$productos = $oCon->getData();
unset($oCon);

foreach($productos as $prod){
    $oItem=new complex('Dispensacion','Codigo',$prod["DISPENSACION"]);
    $dis = $oItem->getData();
    unset($oItem);
    
    $oItem=new complex('A_Entrega_Panales_2025','Id_Entrega_Panales',$prod["Id_Entrega_Panales"]);
    $oItem->id_dispensacion=$dis["Id_Dispensacion"];
    $oItem->save(); 
    unset($oItem);
    
    echo $prod["DISPENSACION"]."<br>";
    
    //exit;

}



?>