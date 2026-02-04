<?php


function consultaDividirPdf($idauditoria)
{
    try {
        $queryObj = new QueryBaseDatos();
        $query = getPaginasSoportes($idauditoria);
        $queryObj->SetQuery($query);
        $archivos = $queryObj->ExecuteQuery('Multiple');
        $zzz = 0;

        foreach ($archivos as $arch) {
            try {
                $zzz++;
                if ($arch["Archivo"] != "" && file_exists($_SERVER['DOCUMENT_ROOT'] . "/IMAGENES/AUDITORIAS/" . $arch["Id_Auditoria"] . '/' . $arch["Archivo"])) {
                    $soportes = explode(",", $arch["Soportes"]);
                    $see = explode(";", $soportes[0]);
                    $ruta = $_SERVER['DOCUMENT_ROOT'] . "/IMAGENES/AUDITORIAS/" . $arch["Id_Auditoria"];
                    $ruta_nueva = $_SERVER['DOCUMENT_ROOT'] . "/SOPORTES_DIVIDIDOS/" . $arch["Id_Auditoria"];
                    $peso = filesize($ruta . "/" . $arch["Archivo"]);
                    /* $file_new = pdf_recreate($ruta . "/" . $arch["Archivo"]); */
                    $pdf_merge = new PDFMerger;
                    $pdf_merge->addPDF($_SERVER['DOCUMENT_ROOT'] . '/IMAGENES/AUDITORIAS/' . $arch["Id_Auditoria"] . '/' . $arch["Archivo"], 'all');
                    $pdf_merge->merge('file', $_SERVER['DOCUMENT_ROOT'] . '/IMAGENES/AUDITORIAS/' . $arch["Id_Auditoria"] . '/' . $arch["Archivo"]);
                    /* $pdf_merge->addPDF($file_new, 'all');
                    $pdf_merge->merge('file', $ruta . "/" . $arch["Archivo"]); */
                    foreach ($soportes as $sop) {
                        try {
                            $detalles = explode(";", str_replace(" ", "", $sop));
                            $tipo = $detalles[0];
                            $idsoporte = $detalles[3];
                            $paginas = [];
                            if ($detalles[2] != "0" && $detalles[2] != "NA") {
                                $pos = strpos($detalles[2], '-');
                                if ($pos !== false) {
                                    $p = explode("-", $detalles[2]);
                                    $x = -1;
                                    for ($j = $p[0]; $j <= $p[(count($p) - 1)]; $j++) {
                                        $x++;
                                        $paginas[$x] = $j;
                                    }
                                } else {
                                    $pos = strpos($detalles[2], '.');
                                    if ($pos !== false) {
                                        $p = explode(".", $detalles[2]);
                                        $x = -1;
                                        for ($j = $p[0]; $j <= $p[(count($p) - 1)]; $j++) {
                                            $x++;
                                            $paginas[$x] = $j;
                                        }
                                    } else {
                                        $pos = strpos($detalles[2], ',');
                                        if ($pos !== false) {
                                            $p = explode(",", $detalles[2]);
                                            $x = -1;
                                            for ($j = $p[0]; $j <= $p[(count($p) - 1)]; $j++) {
                                                $x++;
                                                $paginas[$x] = $j;
                                            }
                                        } else {
                                            $paginas[0] = $detalles[2];
                                        }
                                    }
                                }
                                $nombre = "0_" . $sop["Archivo"] . "_" . $detalles[1] . "_0.pdf";
                                dividir_pdf($ruta, $arch["Archivo"], $ruta_nueva, $nombre, $paginas, $tipo, $idsoporte, $idauditoria);
                            }
                        } catch (Exception $e) {
                            error_log("Error procesando soporte: " . $e->getMessage());
                            continue;
                        }
                    }
                } else {
                    // echo "Sin Soporte";
                }
            } catch (Exception $e) {
                error_log("Error procesando archivo " . $arch["Archivo"] . ": " . $e->getMessage());
                continue;
            }
        }
    } catch (\Throwable $th) {
        header("HTTP/1.0 400 " . $th->getMessage());
        echo json_encode(['message' => $th->getMessage()]);
    }
}

function dividir_pdf($ruta, $archivo, $ruta_final, $nuevo_nombre, $pag, $tipo, $idsoporte, $idauditoria)
{
    try {
        $pdf = new FPDI();
        $paginas = $pdf->setSourceFile($ruta . '/' . $archivo);
        $new_pdf = new FPDI();
        for ($i = 1; $i <= $paginas; $i++) {
            $clave = array_search($i, $pag);
            if ($clave !== false) {
                $new_pdf->AddPage();
                $new_pdf->setSourceFile($ruta . '/' . $archivo);
                $new_pdf->useTemplate($new_pdf->importPage($i));
            }
        }
        if (!file_exists($ruta_final)) {
            mkdir($ruta_final, 0777, true);
        }
        //$new_pdf->Output($ruta_final."/".$nuevo_nombre,'F');
        $fileaws =  $new_pdf->Output("mypdf.pdf", "S");
        $s3 = new AwsS3();
        $ruta = 'dispensacion/auditoria/soportes/';
        $ruta .= "" . $idauditoria . "/" . $tipo . "/";
        $uri = $s3->putObjectBinary($ruta, $fileaws);
        $oItem = new complex('Soporte_Auditoria', "Id_Soporte_Auditoria", $idsoporte);
        $oItem->Archivo = $uri;
        $oItem->save();
        unset($oItem);
    } catch (Exception $e) {
        error_log("Error dividir_pdf: " . $e->getMessage());
        throw $e;
    }
}

function pdf_recreate($f)
{
    try {
        rename($f, str_replace('.pdf', '_.pdf', $f));
        $fileArray = array(str_replace('.pdf', '_.pdf', $f));
        $outputName = $f;
        $cmd = "gs -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile=$outputName ";
        foreach ($fileArray as $file) {
            $cmd .= $file . " ";
        }
        $result = shell_exec($cmd);
        rename($f, str_replace('_.pdf', '.pdf', $f));
        return ($f);
    } catch (Exception $e) {
        error_log("Error pdf_recreate: " . $e->getMessage());
        return false;
    }
}

function getPaginasSoportes($idauditoria)
{
    // global $idauditoria;
    $query = "SELECT SA.Id_Tipo_Soporte as TipoSoporte,  A.Id_Auditoria, A.Archivo, GROUP_CONCAT(CONCAT_WS(';',SA.Id_Tipo_Soporte,TS.Nombre_Radicacion,SA.Paginas,SA.Id_Soporte_Auditoria)) as Soportes
                FROM Soporte_Auditoria SA 
                INNER JOIN Auditoria A ON SA.Id_Auditoria = A.Id_Auditoria
                INNER JOIN Tipo_Soporte TS ON TS.Id_Tipo_Soporte = SA.Id_Tipo_Soporte 
                WHERE SA.Id_Auditoria = $idauditoria";
    return $query;
}
