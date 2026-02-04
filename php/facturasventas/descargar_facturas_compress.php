<?php
include_once '../../class/class.lista.php';
include_once '../../class/class.complex.php';
require_once '../../class/class.consulta.php';

$Fecha1 = isset($_REQUEST['Fecha1']) ? $_REQUEST['Fecha1'] : false;
$Fecha2 = isset($_REQUEST['Fecha2']) ? $_REQUEST['Fecha2'] : false;
$Tipo = isset($_REQUEST['Tipo']) ? $_REQUEST['Tipo'] : false;

$oItem = new complex("Configuracion", "Id_Configuracion", 1);
$config = $oItem->getData();
unset($oItem);

$nit=explode("-", $config["NIT"]);
$nit = str_replace(".", "",  $nit[0]);
$query = ' SELECT F.Codigo AS Factura, F.Fecha_Documento, R.resolution_id, F.Id_Resolucion, R.Codigo
                FROM ' . $Tipo . ' F
                INNER JOIN Resolucion R ON R.Id_Resolucion = F.Id_Resolucion
                WHERE  DATE(Fecha_Documento) BETWEEN "' . $Fecha1 . '" AND "' . $Fecha2 . '"';
$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$facturas = $oCon->getData();
unset($oCon);

$zip = new ZipArchive();
$filename = 'facturas.zip';
if (count($facturas) > 0) {
    if ($zip->open($filename, ZIPARCHIVE::CREATE) === true) {

        $total_facturas=0;
        foreach ($facturas as $key => $factura) {
            $ruta_nueva = "$_SERVER[DOCUMENT_ROOT]" . "/ARCHIVOS/FACTURA_ELECTRONICA_PDF/" . $factura["resolution_id"];

            //$dir_fact = $_SERVER['DOCUMENT_ROOT'].'/ARCHIVOS/FACTURAS_DIS/0_'.$nit.'_'.$factura["Factura"].'_4_0.pdf';
            $nombre_factura = "fv" . getNombre($factura["Codigo"], $factura['Factura'], $factura['Fecha_Documento']) . ".pdf";

            $ruta_fact = $ruta_nueva . "/" . $nombre_factura;
            // echo $ruta_fact; echo '<br>';exit;
            if (file_exists($ruta_fact)) {
                $total_facturas++;
                $zip->addFile($ruta_fact, $factura['Factura'] . '.pdf');
                #$zip->setCompressionIndex($key, ZIPARCHIVE::CM_STORE );
            }

        }
        if($total_facturas>0){

            $zip->close();
            //echo 'creado';
            
            ///Then download the zipped file.
            header('Content-Type: application/zip');
            header('Content-disposition: attachment; filename=' . $filename);
            // header('Content-Length: ' . filesize($filename));
            readfile($filename);
            unlink($filename);
        }
        else{
            echo "Archivos no encontrados";
        }

    }
} else {
    echo 'No existen Facturas en las fechas Seleccionadas';
}

function getNombre($resolucion, $codigo, $fecha)
{
    global $nit;

    // $nit = explode("-", $config['NIT']);
    // $nit = str_replace(".", "", $nit[0]);

    $codigo = (int) str_replace($resolucion, "", $codigo);

    $nombre = str_pad($nit, 10, "0", STR_PAD_LEFT) . "000" . date("y", strtotime($fecha)) . str_pad($codigo, 8, "0", STR_PAD_LEFT);
    return $nombre;
}
