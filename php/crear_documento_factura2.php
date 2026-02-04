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
    
    $oItem = new complex("Configuracion","Id_Configuracion",1);
    $config = $oItem->getData();
    unset($oItem);
    
    $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;
    
    $nit=str_replace(".","",str_replace("-5","",$config["NIT"]));
    
    $ruta_nueva = $_SERVER['DOCUMENT_ROOT']."/ARCHIVOS/FACTURAS_DIS/";
    
        $pdf = new Separar_Pdf();
    
          $archivos = ObtenerArchivo();
          $zzz = 0;
          foreach($archivos as $arch){ $zzz++;
          
            $nombre_factura="0_".$nit."_".$arch["Codigo"]."_4_0.pdf";
            $ruta_fact =  $ruta_nueva."/".$nombre_factura;
            
            if($arch["Tipo_Fact"]=="Homologo"){
            	$tipo = '&Tipo=Homologo';
            }else{
            	$tipo='';
            }
            
            $oItem = new complex("Factura","Id_Factura",$arch["Id_Factura"]);
            $oItem->Actualizado = 1;
            $oItem->save();
            unset($oItem);
            
            echo $zzz." - ".$arch["Codigo"]."<br>";
            include('https://192.168.40.201/php/facturasventas/factura_dis_pdf_unido.php?id='.$arch['Id_Factura'].'&Ruta='.$ruta_fact.$tipo);
            sleep(5);
          }
          
    

    function ObtenerArchivo(){
        global $id;
        
        if($id==1){
            $limit='LIMIT 0,10000';            
        }elseif($id==2){
             $limit='LIMIT 10001,10000';  
        }elseif($id==3){
             $limit='LIMIT 20001,10000';  
        }elseif($id==4){
             $limit='LIMIT 30001,10000';  
        }elseif($id==5){
             $limit='LIMIT 40001,10000';  
        }elseif($id==6){
             $limit='LIMIT 50001,10000';  
        }elseif($id==7){
             $limit='LIMIT 60001,10000';  
        }elseif($id==8){
             $limit='LIMIT 70001,10000';  
        }
        
        
        $query='SELECT F.Id_Factura, F.Codigo, F.Tipo as Tipo_Fact
        
        FROM Factura F 
        WHERE F.Actualizado = 0
        AND F.Procesada =  "true"
        #AND Fecha_Documento <= "2020-06-30 23:59:59"
        ORDER BY F.Id_Factura DESC
        '.$limit;
        
        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $facturas = $oCon->getData();
        unset($oCon);
        
        return ($facturas);
        
    }
    
    function ComprimirArchivos($ruta,$radicado){
    
   	$rootPath = $ruta;

	$zip = new ZipArchive();
	$zip->open($_SERVER['DOCUMENT_ROOT']."/SOPORTES_DIVIDIDOS/".$radicado.".zip", ZipArchive::CREATE | ZipArchive::OVERWRITE);

	$files = new RecursiveIteratorIterator(
	   new RecursiveDirectoryIterator($rootPath),
	   RecursiveIteratorIterator::LEAVES_ONLY
	);
		
	foreach ($files as $name => $file)
	{
	    // Skip directories (they would be added automatically)
	    if (!$file->isDir())
	    {
	        $filePath = $file->getRealPath();
	        $relativePath = substr($filePath, strlen($rootPath) + 1);
	
	        $zip->addFile($filePath, $relativePath);
	    }
	}
	// Zip archive will be created only after closing object
	$zip->close();
	header("Content-type: application/octet-stream");
    header("Content-disposition: attachment; filename=".$radicado.".zip");
    header("Content-length: " . filesize($_SERVER['DOCUMENT_ROOT']."/SOPORTES_DIVIDIDOS/".$radicado.".zip"));
    header("Pragma: no-cache");
    header("Expires: 0");
    ob_clean();
    flush();

    rmdir($_SERVER['DOCUMENT_ROOT']."/SOPORTES_DIVIDIDOS/".$radicado);
    // leemos el archivo creado
    readfile($_SERVER['DOCUMENT_ROOT']."/SOPORTES_DIVIDIDOS/".$radicado.".zip");
    exit; 
    
    }
    
ob_end_flush();
?>