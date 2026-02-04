<?php
	date_default_timezone_set('America/Bogota');

	require_once('../config/start.inc.php');
	include_once('../class/class.querybasedatos.php');
	include_once('../class/class.paginacion.php');
	include_once('../class/class.http_response.php');
    include_once('../class/class.lista.php');
    include_once('../class/class.complex.php');
    include_once('../class/class.consulta.php');
    include_once('../class/PDFMerge/PDFMerger.php');
    
    include_once('../class/class.dividir_pdf.php');

    $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;
    
    $acta ="18";
    $no_sop = 0;
    
    $oItem = new complex("Configuracion","Id_Configuracion",1);
    $config = $oItem->getData();
    unset($oItem);
    
    $archivos = ObtenerArchivo($id);
    $zzz = 0;
    
    foreach($archivos as $arch){ $zzz++;
    
     // echo "<br><br>===============================<br>".$arch["Factura"]." - ".$arch["DIS"]."<br>";
      $ruta_nueva = $_SERVER['DOCUMENT_ROOT']."/SOPORTES_DIVIDIDOS/".$arch["Codigo_Rad"];
        
        if(!file_exists($ruta_nueva) ){
        	mkdir($ruta_nueva, 0777);
        }
            
        $nombre_acta="0_".$nit."_".$arch["Factura"]."_18_0.pdf";
        $ruta_acta =  $ruta_nueva."/".$nombre_acta;
        
        if($arch["Acta_Entrega"]!=""){
            if (!copy($_SERVER['DOCUMENT_ROOT']."/ARCHIVOS/DISPENSACION/ACTAS_ENTREGAS/".$arch["Acta_Entrega"], $ruta_acta)) {
               echo "Esta Factura tiene Acta corrupta o danada: ".$arch["Factura"]."<br>";
               $no_sop++;
            }
        }elseif($arch["Firma_Reclamante"]!=""){      
        	include('https://192.168.40.201/php/dispensaciones/dispensacion_pdf.php?id='.$arch['Id_Dispensacion'].'&Ruta='.$ruta_acta);
        }else{
            echo ($no_sop+1)." - Esta Factura NO Tiene acta ni Firma Wacom: ".$arch["Factura"]."<br>";
            $no_sop++;
        } 
                
             
    }
            
    
    

    function ObtenerArchivo($id){
        
        $query='SELECT R.Codigo as Codigo_Rad, D.Codigo as DIS, F.Codigo as Factura, F.Id_Factura, F.Tipo as Tipo_Fact, A.Archivo, A.Id_Auditoria, D.Acta_Entrega, D.Firma_Reclamante, D.Id_Dispensacion 
                FROM Radicado_Factura RF 
                INNER JOIN Factura F ON F.Id_Factura = RF.Id_Factura
                INNER JOIN Auditoria A ON A.Id_Dispensacion = F.Id_Dispensacion
                INNER JOIN Dispensacion D ON D.Id_Dispensacion = F.Id_Dispensacion
                INNER JOIN Radicado R ON R.Id_Radicado = RF.Id_Radicado
                WHERE R.Estado="PreRadicada" AND R.Fecha_Registro LIKE "%2020-02%"';
        
        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $facturas = $oCon->getData();
        unset($oCon);
        
        return ($facturas);
        
    }
    

?>