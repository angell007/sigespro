
<?php

echo "iniciado el proceso <br>";

?>
<table style="font-size:9px;" border="1">
  <tr>
    <td>#</td>
    <td>Factura</td>
    <td>Generado</td>
  </tr>


  <?php

// sleep(4);
// include_once(__DIR__ . '/../class/class.dividir_pdf.php');
// include_once __DIR__ . '/../class/class.lista.php';
include_once __DIR__ . '/../class/class.querybasedatos.php';
include_once __DIR__ . '/../class/class.complex.php';
include_once __DIR__ . '/../class/class.consulta.php';
// include_once __DIR__ . '/../class/PDFMerge/PDFMerger.php';
// include_once __DIR__ . '/../class/class.dividir_pdf.php';
include "./facturasventas/factura_dis_pdf_class.php";

// $pdf = new Separar_Pdf();
ini_set("memory_limit", "32000M");
ini_set('max_execution_time', 0);

$facturas = isset($_POST['facturas']) ? $_POST['facturas'] : "";
$cargue = isset($_POST['cargue']) ? $_POST['cargue'] : "";
$facturas = explode("\r\n", $facturas);
$cargue = explode("\r\n", $cargue);


// echo "ok"; exit;
$archivos = ObtenerArchivo(implode(",", $facturas));
$oItem = new complex("Configuracion", "Id_Configuracion", 1);
$config = $oItem->getData();
unset($oItem);

$nit = explode("-", $config['NIT']);
$nit = str_replace(".", "", $nit);

$zzz = 0;
foreach ($archivos as $arch) {
    $zzz++;
    $CODIGO_FACTURA = $arch['Factura'];
    echo "<tr>";


    $resolution = $arch['resolution_id'];

    $codigo = (int) str_replace($arch['Codigo_Res'], "", $arch['Factura']);
    $year_factura = date('y', strtotime($arch['Fecha_Documento']));
    $name_file = str_pad($nit[0], 10, "0", STR_PAD_LEFT) . "000" . "$year_factura" . str_pad($codigo, 8, "0", STR_PAD_LEFT);

    $name_file = ("$name_file.pdf");

    $carpeta_factura = $arch['Codigo_Res'] == "NP" ? "NP" : "$arch[resolution_id]";

    $ruta_factura_electronica = "$_SERVER[DOCUMENT_ROOT]/ARCHIVOS/FACTURA_ELECTRONICA_PDF/$carpeta_factura";

    $ruta_factura_electronica .= "/fv$name_file";

            echo "<td>" . $zzz . "</td>";
            echo "<td>" . $CODIGO_FACTURA . "</td>";
    $CARPETA_CARGUES = "$_SERVER[DOCUMENT_ROOT]/ARCHIVOS/CARGUES/";
    $archivo_factura = buscarFactura($arch, $name_file, $cargue[$indice], $CARPETA_CARGUES);

    if (!($archivo_factura)) {
        $oCom = new complex("Factura", "Codigo", $CODIGO_FACTURA);
        $idFactura = $oCom->getData()['Id_Factura'];
        try {
            $factura = new FacturaDis($idFactura, str_replace($_SERVER['DOCUMENT_ROOT'], "", $ruta_factura_electronica));
            $factura->generarPdf();
            // unset($factura);
            echo "<td>Generado</td>";
    // echo ;
        } catch (\Throwable $e) {
            // var_dump($e->getMessage());
            // exit;
        }

    } else {
        echo "<td>Encontrado</td>";
    }

            echo "</tr>";

}
echo "Terminado";
echo "</table>";

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

    $query_capita = ' SELECT D.Codigo as DIS, F.Codigo as Factura, F.Id_Factura_Capita as Id_Factura,IFNULL(D.Fecha_Actual,D2.Fecha_Actual) AS Fecha_Actual ,
                F.Fecha_Documento,
                 IFNULL( D.Acta_Entrega, D2.Acta_Entrega ) AS Acta_Entrega,
                 IFNULL( A.Archivo, A2.Archivo ) AS Archivo,
                 IFNULL( A.Id_Auditoria, A2.Id_Auditoria ) AS Id_Auditoria,
                 IFNULL( D.Firma_Reclamante, D2.Firma_Reclamante ) AS Firma_Reclamante,
                 F.Id_Resolucion,
                 R.resolution_id,
                 R.Codigo as Codigo_Res
                 FROM Factura_Capita F
                 LEFT JOIN Resolucion R ON R.Id_Resolucion = F.Id_Resolucion
                 LEFT JOIN Dispensacion D ON D.Id_Dispensacion = F.Id_Dispensacion
                 WHERE F.Estado_Factura !="Anulada" AND F.Codigo IN ( "' . str_replace(",", '","', $facturas) . '")
                 ';

    //  echo $query; exit;
    try {
        $oCon = new consulta();
        $oCon->setQuery($query);
        $oCon->setTipo('Multiple');
        $facturas = $oCon->getData();
        unset($oCon);
        //code...
    } catch (\Throwable $th) {
        echo $th->getMessage();
        // throw $th;
    }

    return ($facturas);
}

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
