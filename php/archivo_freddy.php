<?php

include_once __DIR__ . '/../class/class.lista.php';
include_once __DIR__ . '/../class/class.querybasedatos.php';
include_once __DIR__ . '/../class/class.complex.php';
include_once __DIR__ . '/../class/class.consulta.php';
include_once __DIR__ . '/../class/PDFMerge/PDFMerger.php';
include_once __DIR__ . '/../class/class.dividir_pdf.php';
include "./facturasventas/factura_dis_pdf_class.php";


ini_set("memory_limit", "32000M");
ini_set('max_execution_time', 0);

$tipo = isset($_REQUEST['tipo']) ? $_REQUEST['tipo'] : "D16";
$facturas = isset($_POST['facturas']) ? $_POST['facturas'] : "";
$cargue = isset($_POST['cargue']) ? $_POST['cargue'] : "";
$facturas = explode("\r\n", $facturas);
$cargue = explode("\r\n", $cargue);

$oItem = new complex("Configuracion", "Id_Configuracion", 1);
$config = $oItem->getData();
unset($oItem);

$nit = explode("-", $config['NIT']);
$nit = str_replace(".", "", $nit);

$ruta_base = "$_SERVER[DOCUMENT_ROOT]/ARCHIVOS/MEDIMAS/";

if (!file_exists($ruta_base)) {
    mkdir($ruta_base, 0777);
} else {
    deleteDirectory($ruta_base);
    mkdir($ruta_base, 0777);
}
$archivos = ObtenerArchivo(implode(",", $facturas));

// header("Content-type:application/json");
// echo json_encode($archivos); exit;
echo '<table style="font-size:9px;" border="1">
<tr>   <td>#</td>   <td>Factura</td>   <td>Dispensacion</td>   <td>Fecha Entrega</td>   <td>Cargue</td>    <td>Acta</td>   <td>Firma</td>   <td>Factura</td>  <td>Soporte</td> <td>Archivo PDF</td>
</tr>';

$i = 0;
$zzz = 0;

$NOMBE_BASE = "$tipo" . "_$nit[0]_";
$CARPETA_CARGUES = "$_SERVER[DOCUMENT_ROOT]/ARCHIVOS/CARGUES/";
foreach ($archivos as $arch) {
    $CODIGO_FACTURA = $arch['Factura'];
    $resolution = $arch['resolution_id'];
    $indice = array_search($CODIGO_FACTURA, $facturas);
    $carpeta = $ruta_base . "$NOMBE_BASE$arch[Codigo_Res]/";
    $codigo = (int) str_replace($arch['Codigo_Res'], "", $arch['Factura']);

    $archivo_pdf = $carpeta . "$NOMBE_BASE$arch[Codigo_Res]_$codigo.pdf";
    $zzz++;
    echo "<tr>";
    echo "<td>" . $zzz . "</td><td>" . $CODIGO_FACTURA . "</td><td>" . $arch["DIS"] . "</td>
                 <td>" . $arch["Fecha_Documento"] . "</td>";

    if (!file_exists($carpeta)) {
        mkdir($carpeta, 0777, true);
    }

    $year_factura = date('y', strtotime($arch['Fecha_Documento']));
    $name_file = str_pad($nit[0], 10, "0", STR_PAD_LEFT) . "000" . $year_factura . str_pad($codigo, 8, "0", STR_PAD_LEFT);

    $name_file = ("$name_file.pdf");
    try {
        $archivo_factura = buscarFactura($arch, $name_file, $cargue[$indice], $CARPETA_CARGUES);
        $archivo_cargue = buscarCargue($CARPETA_CARGUES, $cargue[$indice]);
        $archivo_soporte = buscarSoporte($arch);
        $archivo_firma = buscarFirma($arch);
        $archivo_acta = buscarActa($arch);

        $archivos_capita = buscarSoportesCapita($arch, $CARPETA_CARGUES);
        //code...
    } catch (\Throwable $th) {
        var_dump($th);
        exit;
    }

    
      // $pdf = new FPDF_Merge();
      // $merge->add('doc1.pdf');
      // $merge->add('doc2.pdf');
    //  echo json_encode($archivo_acta);exit;
    $pdf = new PDFMerger();
    try {
        foreach ($archivos_capita as $capita) {
            $pdf->addPDF($capita);
        }
      //   $archivo_factura ? $pdf->add($archivo_factura, '1') : '';
      //   $archivo_acta ? $pdf->add($archivo_acta) : '';
      //   $archivo_firma ? $pdf->add($archivo_firma) : '';
      //   $archivo_soporte ? $pdf->add($archivo_soporte) : '';
      //   $archivo_cargue ? $pdf->add($archivo_cargue) : '';
        $archivo_factura ? $pdf->addPDF($archivo_factura, '1') : '';
        $archivo_acta ? $pdf->addPDF($archivo_acta) : '';
        $archivo_firma ? $pdf->addPDF($archivo_firma) : '';
        $archivo_soporte ? $pdf->addPDF($archivo_soporte) : '';
        $archivo_cargue ? $pdf->addPDF($archivo_cargue) : '';

        //code...
    } catch (\Throwable $th) {
        var_dump($th);
        exit;
    }

    echo $archivo_cargue ? "<td>Cargue</td>" : "<td>-</td>";

    echo $archivo_acta ? "<td>Acta</td>" : "<td>-</td>";

    echo $archivo_firma ? "<td>Firma</td>" : "<td>-</td>";
    echo $archivo_factura ? "<td>Factura</td>" : "<td>-</td>";
    if ($archivo_soporte || $archivos_capita) {
        echo $archivos_capita ? "<td>Capita</td>" : false;
        echo $archivo_soporte ? "<td>Soporte</td>" : false;
    } else {
        echo "<td>-</td>";
    }
    try {

        $pdf->merge('file', $archivo_pdf);
        echo "<td>OK</td>";
    } catch (\Throwable $e) {
       echo $e->getMessage();
        echo "<td>Error</td>";
    }
    echo "</tr>";
}

$descarga = ComprimirArchivos($ruta_base, "Medimas", 0);
echo "<br><br><a href=$descarga  target='_blank' >DESCARGAR Medimas.zip </a><br><br>";

echo "</table>";

function deleteDirectory($dir)
{
    if (!$dh = @opendir($dir)) {
        return;
    }

    while (false !== ($current = readdir($dh))) {
        if ($current != '.' && $current != '..') {
            if (!@unlink($dir . '/' . $current)) {
                deleteDirectory($dir . '/' . $current);
            }

        }
    }
    closedir($dh);
    @rmdir($dir);
}

function ComprimirArchivos($ruta, $radicado, $no_sop)
{
    try {
        $rootPath = $ruta;
        $zip = new ZipArchive();
        $zip->open("$ruta" . $radicado . ".zip", ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootPath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($rootPath) - 1);

                $zip->addFile($filePath, $relativePath);
            }
        }
        $zip->close();
        return ($_SERVER["REQUEST_SCHEME"] . "://" . $_SERVER["HTTP_HOST"] . "/ARCHIVOS/MEDIMAS/$radicado.zip");
    } catch (\Throwable $th) {
        return "null";
    }
}

function mergepdf(){


}
function CrearArrayPaginas(array $indices)
{
    $pages = [];
    $superior = $indices[1] ? $indices[1] : $indices[0];
    for ($i = $indices[0]; $i <= $superior; $i++) {
        array_push($pages, $i);
    }
    return $pages;
}

/**
 * Busca el Cargue en la carpeta específica
 * @param string $carpeta_cargues Ruta base para buscar los cargues
 * @param string $new_cargue Ruta de destino del cargue
 * @param string $id_cargue Id cargue a buscar
 * @return string ok si encuentra el cargue o 'No Encontrado' en caso contrario
 */
function buscarCargue(string $carpeta_cargues, string $id_cargue)
{
    $lote1 = "$carpeta_cargues/TRABAJO 1 PRINCIPAL/$id_cargue/CARGUE $id_cargue.pdf";
    $lote2 = "$carpeta_cargues/TRABAJO 2 SEGUNDO/cargues 2/$id_cargue.pdf";
    if (!file_exists($lote1)) {
        if (!file_exists($lote2)) {
            return false;
        }
        return $lote2;
    }
    return $lote1;

}

/**
 *
 */
function buscarFactura($arch, $name_file, $id_cargue, $carpeta_cargues)
{
    $carpeta_factura = $arch['Codigo_Res'] == "NP" ? "NP" : "$arch[resolution_id]";

    $factura_cargue = "$carpeta_cargues/TRABAJO 1 PRINCIPAL/$id_cargue/$arch[Factura]";
    if (file_exists($factura_cargue)) {
        return $factura_cargue;
    }

    $ruta_factura_electronica = "$_SERVER[DOCUMENT_ROOT]/ARCHIVOS/FACTURA_ELECTRONICA_PDF/$carpeta_factura/fv$name_file";

    if (!file_exists("$ruta_factura_electronica")) {
        return false;
    } else {
        return $ruta_factura_electronica;
    }
}
function buscarSoporte($arch)
{
    # code...
    $carpeta_soporte = "$_SERVER[DOCUMENT_ROOT]/IMAGENES/AUDITORIAS/$arch[Id_Auditoria]/";
    if ($arch["Archivo"] != "") {
        //  , "$carpeta/$tipo" . "_$nit[0]_$CODIGO_FACTURA" . "SOPORTE.pdf"
        if (!file_exists("$carpeta_soporte/$arch[Archivo]")) {
            return false;
        }
        return "$carpeta_soporte/$arch[Archivo]";
    }
    return false;

}
function buscarActa($arch)
{
    $carpeta_entregas = $_SERVER['DOCUMENT_ROOT'] . "/ARCHIVOS/DISPENSACION/ACTAS_ENTREGAS/";

    if ($arch["Acta_Entrega"] != "") {
        if (!file_exists($carpeta_entregas . $arch["Acta_Entrega"])) {
            return false;
        }
        return $carpeta_entregas . $arch["Acta_Entrega"];
    }
    return false;
}

function buscarFirma($arch)
{
    $carpeta_firma = "$_SERVER[DOCUMENT_ROOT]/IMAGENES/FIRMAS-DIS/";
    if ($arch["Firma_Reclamante"] != "") {
        if (!file_exists("$carpeta_firma$arch[Firma_Reclamante]")) {
            return false;
        }
        return "$carpeta_firma$arch[Firma_Reclamante]";
    }
    return false;
}

function buscarSoportesCapita($arch, $carpeta_cargues)
{
    $factura = "$carpeta_cargues/CAPITAS/$arch[Factura]/FACTURA.pdf";
    $soporte1 = "$carpeta_cargues/CAPITAS/$arch[Factura]/1.pdf";
    $soporte2 = "$carpeta_cargues/CAPITAS/$arch[Factura]/2.pdf";

    $archivos_capita = [];
    if (file_exists($factura)) {
        array_push($archivos_capita, $factura);
    }

    if (file_exists($soporte1)) {
        array_push($archivos_capita, $soporte1);
    }

    if (file_exists($soporte2)) {
        array_push($archivos_capita, $soporte2);
    }

    return count($archivos_capita) > 0 ? $archivos_capita : false;

}
function ObtenerArchivo($facturas)
{

    $query = 'SELECT D.Codigo as DIS, F.Codigo as Factura, F.Id_Factura,IFNULL(D.Fecha_Actual,D2.Fecha_Actual) AS Fecha_Actual ,
                  F.Fecha_Documento,
                  IFNULL( D.Acta_Entrega, D2.Acta_Entrega ) AS Acta_Entrega,
                  IFNULL( A.Archivo, A2.Archivo ) AS Archivo,
                  IFNULL( A.Id_Auditoria, A2.Id_Auditoria ) AS Id_Auditoria,
                  IFNULL( D.Firma_Reclamante, D2.Firma_Reclamante ) AS Firma_Reclamante,
                  F.Id_Resolucion,
                  R.resolution_id,
                  R.Codigo as Codigo_Res
                  FROM Factura F
                  LEFT JOIN Resolucion R ON R.Id_Resolucion = F.Id_Resolucion
                  LEFT JOIN Dispensacion D ON D.Id_Dispensacion = F.Id_Dispensacion
                  LEFT JOIN Dispensacion D2 ON D2.Id_Dispensacion = F.Id_Dispensacion2
                  LEFT JOIN Auditoria A ON A.Id_Dispensacion = D.Id_Dispensacion
                  LEFT JOIN Auditoria A2 ON A2.Id_Dispensacion = D2.Id_Dispensacion

                  WHERE F.Estado_Factura !="Anulada" AND F.Codigo IN ( "' . str_replace(",", '","', $facturas) . '")';

    // echo $query; exit;
    $query_capita = 'SELECT Null AS DIS, F.Codigo AS Factura, Concat("CAP",F.Id_Factura_Capita) AS Id_Factura, null AS Fecha_Actual, F.Fecha_Documento, null AS Acta_Entrega, null AS Archivo, null AS Id_Auditoria, null AS Firma_Reclamante, F.Id_Resolucion, R.resolution_id, R.Codigo AS Codigo_Res
         FROM Factura_Capita F
         LEFT JOIN Resolucion R ON R.Id_Resolucion = F.Id_Resolucion
         WHERE F.Estado_Factura !="Anulada" AND F.Codigo IN ( "' . str_replace(",", '","', $facturas) . '")
           ';

    $consulta = "($query) UNION ($query_capita)";
    //  echo $consulta; exit;
    $oCon = new consulta();
    $oCon->setQuery($consulta);
    $oCon->setTipo('Multiple');
    $facturas = $oCon->getData();
    unset($oCon);
    // header("Content-type:application/json");
    // echo json_encode($facturas);exit;
    return ($facturas);
}
