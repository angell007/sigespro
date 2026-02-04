<?php

ini_set("memory_limit","32000M");
ini_set('max_execution_time', 0);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
//header('Content-Type: application/json');

require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');
require_once('../class/html2pdf.class.php');
include_once('../class/NumeroALetra.php');


$facturas = getFacturas();


foreach($facturas as $factu){
    
        $id=$factu["Id_Factura_Administrativa"];
        $ruta = $_SERVER['DOCUMENT_ROOT'] . "ARCHIVOS-FREDDY/". $factu["Codigo"].".pdf";
        include ("test-archivo.php");
        
        echo $factu["Codigo"]."-".$ruta."<br>";   
       
        ob_start(); // Se Inicializa el gestor de PDF 
        try{
            // CREO UN PDF CON LA INFORMACION COMPLETA PARA DESCARGAR
           $html2pdf = new HTML2PDF('P', 'LETTER', 'Es', true, 'UTF-8', array(4, 4, 4, 4));
           $html2pdf->writeHTML($content);
           $content = ob_end_clean();
           $html2pdf->Output($ruta,"F"); // LA D SIGNIFICA DESCARGAR, 'F' SE PODRIA HACER PARA DEJAR EL ARCHIVO EN UNA CARPETA ESPECIFICA
           unset($html2pdf);
        }catch(HTML2PDF_exception $e) {
            echo $e;
            exit;
        }


       sleep(0.05);
           
}


/* FUNCIONES BASICAS */
function fecha($str)
{
	$parts = explode(" ",$str);
	$date = explode("-",$parts[0]);
	return $date[2] . "/". $date[1] ."/". $date[0];
}

function getFacturas(){
    $query = "SELECT * FROM Factura_Administrativa WHERE Fecha LIKE '%2024-08-27%' AND Id_Cliente=860011153 AND Estado_Factura='Pagada' AND Procesada='true' AND Id_Factura_Administrativa>=10247";

    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $facturas = $oCon->getData();
    unset($oCon);
    
    
    return $facturas;
}



?>