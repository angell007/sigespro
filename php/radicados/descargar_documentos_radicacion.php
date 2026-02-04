<?php
ini_set("memory_limit", "320000M");
ini_set('max_execution_time', 0);

date_default_timezone_set('America/Bogota');

require_once('../../config/start.inc.php');
include_once('../../class/class.querybasedatos.php');
include_once('../../class/class.paginacion.php');
include_once('../../class/class.http_response.php');
include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
include_once('../../class/PDFMerge/PDFMerger.php');

include_once('../../class/class.dividir_pdf.php');

$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;

$acta = "18";
$no_sop = 0;

$oItem = new complex("Configuracion", "Id_Configuracion", 1);
$config = $oItem->getData();
unset($oItem);

$oItem = new complex("Radicado", "Id_Radicado", $id);
$rad = $oItem->getData();
unset($oItem);

if ($rad["Numero_Radicado"] != "") {
    $radicado = $rad["Numero_Radicado"];
} else {
    $radicado = $id;
}

$radicado = $rad["Codigo"];
// echo $radicado; exit;
$nit = str_replace(".", "", str_replace("-5", "", $config["NIT"]));

$ruta_nueva = $_SERVER["DOCUMENT_ROOT"] . "/SOPORTES_DIVIDIDOS/" . $radicado;

if (!file_exists($ruta_nueva)) {
    mkdir($ruta_nueva, 0777);
} else {
    array_map('unlink', glob("$ruta_nueva/*.*"));
    rmdir($ruta_nueva);

    mkdir($ruta_nueva, 0777);
}



$contenido = '';
if ($id && $radicado!='') {
    try {

        $archivos = ObtenerArchivo($id);
    } catch (\Throwable $e) {
        echo $e->getMessage();
    }
    $zzz = 0;
  echo "<table border='1'> <tr><td>#</td><td>Factura</td><td>DIS</td><td>Tipo Servicio</td><td>Acta Entrega</td><td>Factura</td><td>Xml</td><td>Soportes</td><td></td></tr>";
    // $pdf_factura = new FacturaVentaPdf(null, null);
    foreach ($archivos as $arch) {
        $zzz++;
        armarFila($ruta_nueva, $arch, $zzz);
        
        continue;
    }
    // echo json_encode($archivos); exit;

   echo "</table>";
    $descarga = ComprimirArchivos("$ruta_nueva/", $radicado, $no_sop);
   echo "<br><br><a href= '" . $descarga . "'>DESCARGAR " . $radicado . ".zip </a><br><br>";
   echo  $descarga;
    echo $contenido;

} else {
    echo "No existe Id de Radicación";
}

/*}else{
        header("Content-type: application/octet-stream");
        header("Content-disposition: attachment; filename=".$radicado.".zip");
        header("Content-type: application/octet-stream");
        header("Content-disposition: attachment; filename=".$radicado.".zip");
        header("Content-length: " . filesize($_SERVER['DOCUMENT_ROOT']."/SOPORTES_DIVIDIDOS/".$radicado.".zip"));
        header("Pragma: no-cache");
        header("Expires: 0");
        ob_clean();
        flush();
        
        readfile($_SERVER['DOCUMENT_ROOT']."/SOPORTES_DIVIDIDOS/".$radicado.".zip");
        exit; 
    }*/

function armarFila($ruta,  $arch, $zzz)
{
    global $ruta_nueva, $nit, $contenido, $pdf_factura;
    try {
        $codigo_factura = $arch["Factura"];
       echo "<tr>";
       echo "<td>" . $zzz . "</td><td>" .$codigo_factura . "</td><td>" . $arch["DIS"] . "</td><td>" . $arch["Tipo_Servicio"] . "</td>";

        $nombre_factura = "0_" . $nit . "_" .$codigo_factura . "_4_0.pdf";
        $nombre_acta = "0_" . $nit . "_" .$codigo_factura . "_18_0.pdf";
        $ruta_fact =  $ruta_nueva . "/" . $nombre_factura;
        $ruta_acta =  $ruta_nueva . "/" . $nombre_acta;

        if ($arch["Tipo_Fact"] == "Homologo") {
            $tipo = '&Tipo=Homologo';
        } else {
            $tipo = '';
        }

        if ($arch["Acta_Entrega"] != "") {
            if (!copy($_SERVER['DOCUMENT_ROOT'] . "/ARCHIVOS/DISPENSACION/ACTAS_ENTREGAS/" . $arch["Acta_Entrega"], $ruta_acta)) {
                if (!copy($arch["Acta_Entrega"], $ruta_acta)) {
                   echo "<td>Acta Dañada</td>";
                } else
                   echo "<td>Acta OK</td>";
            } else {
               echo "<td>Acta OK</td>";
            }
        } elseif ($arch["Firma_Reclamante"] != "") {

            // include('../dispensaciones/dispensacion_pdf_class.php');
            // $pdf_dis= new DispensacionPdf( $arch['Id_Dispensacion'], $ruta_fact);
            // $pdf_dis->generarPdf();
            // include($ruta.'/php/dispensaciones/dispensacion_pdf.php?id=' . $arch['Id_Dispensacion'] . '&Ruta=' . $ruta_acta);
        } else {
           echo "<td>Esta Factura NO Tiene acta ni Firma Wacom:</td>";
        }
        $ruta_relativa = explode("$_SERVER[DOCUMENT_ROOT]/",  $ruta_fact);        //    echo json_encode($ruta_fact);exit;

        $codigo = (int)str_replace($arch['Codigo_Res'], "", $codigo_factura);
        // echo $arch['Fecha_Documento']; exit;
        
        
        $name_file = str_pad($nit, 10, "0", STR_PAD_LEFT) . "000" . date("y", strtotime($arch['Fecha_Documento'])) . str_pad($codigo, 8, "0", STR_PAD_LEFT);


 
            // $pdf_factura->setValues($arch['Id_Factura'], $ruta_relativa[1]);
            // $pdf_factura->generarPdf();
     
        $ruta_factura_dis ="/ARCHIVOS/FACTURA_ELECTRONICA_PDF/". $arch["resolution_id"] . '/fv' . $name_file . '.pdf' ;
        // echo ($_SERVER['HTTP_HOST'].$ruta_factura_dis); exit;
        if (copy($_SERVER['DOCUMENT_ROOT'].$ruta_factura_dis, $ruta_fact))
           echo "<td> Factura OK </td>";
        else
           echo  "<td> Sin Pdf de Factura </td>";


        $xml =  '/home/sigesproph/api-dian.sigesproph.com.co/api-dian/storage/app/xml/1/' . $arch["resolution_id"] . '/fv' . $name_file . '.xml';
        $ruta_xml = $ruta_nueva . '/' . $codigo_factura . '.xml';
        // echo $xml; exit;

        if (!copy($xml, $ruta_xml)) {
           echo "<td> Sin xml de la factura</td>";
        } else {
           echo "<td>Archivo XML OK</td>";
        }



        if ($arch["Nombres_Soportes"] != "") {
           echo "<td>Tiene Soportes</td>";
            $soportes = explode(",", $arch["Nombres_Soportes"]);
            $detalles = explode(",", $arch['Soportes']);
            $index = 0;
            foreach ($soportes as $sop) {
                $tipo = explode(";", $detalles[$index]);
                $index++;
                $nombre = "0_" . $nit . "_" .$codigo_factura . "_" . $tipo[1] . "_0.pdf";
                $ruta_soporte = $ruta_nueva . '/' . $nombre;
                copy($sop, $ruta_soporte);
                //$contenido .= "<td>".$nombre."</td>";
                // $pdf->dividir_pdf($ruta, "3-" . $arch["Archivo"], $ruta_nueva, $nombre, $paginas);
                //$contenido .= "<td>Listo</td>";
            }
        } else {
           echo "<td>No Tiene Soportes</td>";
        }


        // 

        if (file_exists($_SERVER['DOCUMENT_ROOT'] . "/SOPORTES_DIVIDIDOS/ActaMedimas.pdf")) {


            $nombre_acta_med = "0_" . $nit . "_" .$codigo_factura . "_57_0.pdf";
            $ruta_acta_med =  $ruta_nueva . "/" . $nombre_acta_med;
           echo "<td>Acta Medimas OK</td>";
            $pdf_merge2 = new PDFMerger;
            $pdf_merge2->addPDF($_SERVER['DOCUMENT_ROOT'] . "/SOPORTES_DIVIDIDOS/ActaMedimas.pdf", 'all');
            $pdf_merge2->merge('file', $ruta_acta_med);
        } else {
           echo "<td> No existe Acta Medimas</td>";
        }
    } catch (Exception $e) {
        echo $e->getMessage();

       echo '<br> -- ' . file_exists($_SERVER['DOCUMENT_ROOT'] . "/ARCHIVOS/FACTURAS_DIS/" . $nombre_factura) . ' -- <br> ';
       echo '<br>' . $_SERVER['DOCUMENT_ROOT'] . "/ARCHIVOS/FACTURAS_DIS/" . $nombre_factura . '<br>';
        //echo '<br>'.$_SERVER['DOCUMENT_ROOT']."/IMAGENES/AUDITORIAS/".$arch["Id_Auditoria"] .'<br>';
        //echo $_SERVER['DOCUMENT_ROOT']."/SOPORTES_DIVIDIDOS/ActaMedimas.pdf". '<br>';
       echo "Error -> <br>";
        echo $e;
    }
   echo "</tr>";
}

function ObtenerArchivo($id)
{

    $query = 'SELECT D.Codigo as DIS, 
                       F.Codigo as Factura, 
                       F.Fecha_Documento,
                       F.Id_Factura, 
                       F.Tipo as Tipo_Fact, 
                       A.Archivo, TS.Nombre as Tipo_Servicio,
                       A.Id_Auditoria, 
                       D.Acta_Entrega, 
                       D.Firma_Reclamante, 
                       D.Id_Dispensacion, 
                       (SELECT GROUP_CONCAT(CONCAT_WS(";",SA.Id_Tipo_Soporte,TS.Nombre_Radicacion,SA.Paginas)) 
                       FROM Soporte_Auditoria SA 
                       INNER JOIN Tipo_Soporte TS ON TS.Id_Tipo_Soporte = SA.Id_Tipo_Soporte 
                       WHERE SA.Id_Auditoria = A.Id_Auditoria and SA.Archivo IS NOT NULL) AS Soportes,
                       (SELECT GROUP_CONCAT(CONCAT_WS(";", SA.Archivo)) 
                       FROM Soporte_Auditoria SA 
                       INNER JOIN Tipo_Soporte TS ON TS.Id_Tipo_Soporte = SA.Id_Tipo_Soporte 
                       WHERE SA.Id_Auditoria = A.Id_Auditoria AND SA.Archivo IS NOT NULL) AS Nombres_Soportes, 
                       R.resolution_id, 
                       R.Codigo as Codigo_Res
                FROM Radicado_Factura RF 
                LEFT JOIN Factura F ON F.Id_Factura = RF.Id_Factura
                LEFT JOIN Resolucion R ON R.Id_Resolucion = F.Id_Resolucion
                LEFT JOIN Dispensacion D ON D.Id_Dispensacion = F.Id_Dispensacion
                LEFT JOIN Tipo_Servicio TS ON TS.Id_Tipo_Servicio = D.Id_Tipo_Servicio
                LEFT JOIN Auditoria A ON A.Id_Dispensacion = D.Id_Dispensacion or A.Id_Auditoria = D.Id_Auditoria
                
                WHERE RF.Id_Radicado =' . $id . '';
    // echo $query; exit;
    $oCon = new consulta();
    $oCon->setQuery($query);
    $oCon->setTipo('Multiple');
    $facturas = $oCon->getData();
    unset($oCon);

    return ($facturas);
}
function pdf_recreate($f)
{

    rename($f, str_replace('.pdf', '_.pdf', $f));

    $fileArray = array(str_replace('.pdf', '_.pdf', $f));

    $outputName = $f;
    $cmd = "gs -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile=$outputName ";

    // C:\Program Files\gs\gs9.54.0

    foreach ($fileArray as $file) {
        $cmd .= $file . " ";
    }
    $result = shell_exec($cmd);
    //unlink(str_replace('.pdf','_.pdf',$f));
    rename($f, str_replace('_.pdf', '.pdf', $f));

    return ($f);
}
function ComprimirArchivos($ruta, $radicado, $no_sop)
{
    try {

        $rootPath = $ruta;
        // echo $rootPath; exit;
        $zip = new ZipArchive();
        $zip->open($_SERVER['DOCUMENT_ROOT'] . "/SOPORTES_DIVIDIDOS/" . $radicado . ".zip", ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootPath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            // Skip directories (they would be added automatically)
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($rootPath) );

                $zip->addFile($filePath, $relativePath);
            }
        }
        // Zip archive will be created only after closing object
        $zip->close();
        /*if($no_sop==0){
	    header("Content-type: application/octet-stream");
        header("Content-disposition: attachment; filename=".$radicado.".zip");
        header("Content-length: " . filesize($_SERVER['DOCUMENT_ROOT']."/SOPORTES_DIVIDIDOS/".$radicado.".zip"));
        header("Pragma: no-cache");
        header("Expires: 0");
        ob_clean();
        flush();
        
        readfile($_SERVER['DOCUMENT_ROOT']."/SOPORTES_DIVIDIDOS/".$radicado.".zip");
        exit; 
    
	}else{ */
        //code...
        return ($_SERVER["REQUEST_SCHEME"] . "://" . $_SERVER["HTTP_HOST"] . "/SOPORTES_DIVIDIDOS/$radicado.zip");
    } catch (\Throwable $th) {
        //throw $th;
        return "null";
    }
    //}
}

ob_end_flush();
