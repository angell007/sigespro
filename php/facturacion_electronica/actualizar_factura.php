<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-type:application/json');

include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';
include_once '../../class/class.contabilizar.php';
require_once '../../class/class.php_mailer.php';

require_once '../../class/class.qr.php';

$dian_response = (isset($_REQUEST['datos']) ? $_REQUEST['datos'] : '');
$tipo_factura = (isset($_REQUEST['tipo']) ? $_REQUEST['tipo'] : '');
$id_factura = (isset($_REQUEST['id']) ? $_REQUEST['id'] : '');

$contabilizar = new Contabilizar(true);
$dian_response = json_decode($dian_response, true);

$oItem = new complex($tipo_factura, "Id_" . $tipo_factura, $id_factura);
$factura = $oItem->getData();
unset($oItem);

$oItem = new complex("Resolucion", "Id_Resolucion", $factura['Id_Resolucion']);
$resolucion = $oItem->getData();
unset($oItem);

$queryConfig = "SELECT C.*,(SELECT D.Nombre FROM Departamento D WHERE D.Id_Departamento=C.Id_Departamento) as Departamento, (SELECT M.Nombre FROM Municipio M WHERE M.Id_Municipio=C.Id_Municipio) as Ciudad FROM Configuracion C WHERE C.Id_Configuracion=1";

$oCon = new consulta();
$oCon->setQuery($queryConfig);
$configuracion = $oCon->getData();
unset($oCon);

$tip = '';
if ($tipo_factura == "Factura_Venta") {
    $tip = '_Venta';
}
$query = 'SELECT IFNULL(SUM(Cantidad*Precio' . $tip . '),0) as Total, IFNULL(SUM((Cantidad*Precio' . $tip . ')*(Impuesto/100)),0) as Total_Iva, IFNULL(SUM(ROUND(Cantidad*Descuento)),0) as Descuento, Impuesto FROM Producto_' . $tipo_factura . ' WHERE Id_' . $tipo_factura . '=' . $id_factura;

if ($tipo_factura == "Factura_Administrativa") {
    $query = 'SELECT IFNULL(SUM(Cantidad*Precio),0) as Total, IFNULL(SUM((Cantidad*Precio)*(Impuesto/100)),0) as Total_Iva, IFNULL(SUM(ROUND(Cantidad*Descuento)),0) as Descuento, Impuesto
    FROM Descripcion_' . $tipo_factura . '
    WHERE Id_' . $tipo_factura . '=' . $id_factura;
} elseif ($tipo_factura == "Factura_Capita") {

    $tip = '';

    $query = 'SELECT IFNULL(SUM(Cantidad*Precio),0) as Total, IFNULL(SUM((Cantidad*Precio)*(Impuesto/100)),0) as Total_Iva, IFNULL(SUM(ROUND(Cantidad*Descuento)),0) as Descuento, Impuesto
            FROM Descripcion_' . $tipo_factura . '
            WHERE Id_' . $tipo_factura . '=' . $id_factura;
}

$oCon = new consulta();
$oCon->setQuery($query);
$totales = $oCon->getData();
unset($oCon);

try {
    $cliente = getCliente();
    $respuesta_dian = isset($dian_response['Json']) ? $dian_response['Json'] : $dian_response;

    // Verificar si la API DIAN indicó errores explícitamente
    $hasErrors = isset($dian_response['hasErrors']) ? $dian_response['hasErrors'] : false;
    $errorMessage = isset($dian_response['errorMessage']) ? $dian_response['errorMessage'] : null;

    // Si hay errores explícitos de la API DIAN, agregarlos a la respuesta
    if ($hasErrors && $errorMessage) {
        $respuesta_dian['hasErrors'] = true;
        $respuesta_dian['errorMessage'] = $errorMessage;
    }

    $respuesta = GenerarFactura($respuesta_dian);
    echo json_encode($respuesta);
} catch (Exception $e) {
    http_response_code(403);
    echo json_encode($e->getMessage());
}



function GenerarFactura($respuesta_dian)
{
    global $tipo_factura, $id_factura, $resolucion, $contabilizar, $factura;

    // Verificar primero si la API DIAN indicó errores explícitamente
    $hasErrors = isset($respuesta_dian['hasErrors']) ? $respuesta_dian['hasErrors'] : false;
    $errorMessage = isset($respuesta_dian['errorMessage']) ? $respuesta_dian['errorMessage'] : null;

    $aplication_response = $respuesta_dian["ResponseDian"]["Envelope"]["Body"]["SendBillSyncResponse"]["SendBillSyncResult"]["XmlBase64Bytes"];
    $aplication_response = base64_decode($aplication_response);

    $cufe = $respuesta_dian['cufe'];
    $qr = GetQr($cufe);
    $error = $respuesta_dian["ResponseDian"]["Envelope"]["Body"]["SendBillSyncResponse"]["SendBillSyncResult"]['ErrorMessage'];
    $errores_dian = isset($error) ? $error : [];

    if (count($errores_dian) > 0) {
        $errores_dian = implode(" - ", $errores_dian);
    } else {
        $errores_dian = "";
    }

    $statusMessage = $respuesta_dian["ResponseDian"]["Envelope"]["Body"]["SendBillSyncResponse"]["SendBillSyncResult"]["StatusMessage"];
    $isValid = $respuesta_dian["ResponseDian"]["Envelope"]["Body"]["SendBillSyncResponse"]["SendBillSyncResult"]["IsValid"];

    // Si hay errores en ErrorMessage (y no es "procesado anteriormente"), considerar como error
    $tiene_errores = !empty($errores_dian) && strpos($errores_dian, "procesado anteriormente") === false;

    // También verificar si el StatusMessage indica error
    $status_message_error = !empty($statusMessage) && (
        stripos($statusMessage, "error") !== false ||
        stripos($statusMessage, "rechazado") !== false ||
        stripos($statusMessage, "invalid") !== false
    );

    // Si la API DIAN indicó errores explícitamente, priorizar esa información
    if ($hasErrors) {
        $estado = "false";
        if ($errorMessage) {
            $respuesta_dian['Respuesta'] = $errorMessage;
        } else {
            $respuesta_dian['Respuesta'] = $statusMessage;
        }
    } elseif (strpos($errores_dian, "procesado anteriormente") !== false) {
        $estado = "true";
        $respuesta_dian['Respuesta'] = $statusMessage;
    } elseif ($tiene_errores || $status_message_error || $isValid != "true") {
        // Si hay errores o IsValid no es "true", considerar como error
        $estado = "false";
        $respuesta_dian['Respuesta'] = !empty($errores_dian) ? $errores_dian . " - " . $statusMessage : $statusMessage;
    } else {
        $estado = $isValid;
        $respuesta_dian['Respuesta'] = $statusMessage;
    }
    if ($estado == "true") {
        $oItem = new complex($tipo_factura, "Id_" . $tipo_factura, $id_factura);
        $oItem->Cufe = $cufe;
        $oItem->Codigo_Qr = $qr;
        $oItem->Procesada = $estado;
        $oItem->save();
        unset($oItem);

        $nit = getNit();
        $ruta_nueva = "/ARCHIVOS/FACTURA_ELECTRONICA_PDF/" . $resolucion["resolution_id"];

        if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $ruta_nueva)) {
            mkdir($_SERVER['DOCUMENT_ROOT'] . $ruta_nueva, 0777, true);
        }

        $nombre_factura = "fv" . getNombre() . ".pdf";
        $ruta_fact = $ruta_nueva . "/" . $nombre_factura;

        if ($tipo_factura == "Factura") {
            include 'factura_np_pdf_class.php';
        } elseif ($tipo_factura == "Factura_Venta") {
            include 'factura_venta_pdf_directo.php';
        } elseif ($tipo_factura == "Factura_Capita") {
            // $url = ('https://sigesproph.com.co/php/facturacion_electronica/factura_capita_pdf.php');
        } elseif ($tipo_factura == "Factura_Administrativa") {
            include 'factura_administrativa_pdf_class.php';
        }
        try {

            $pdf = new FacturaVentaPdf($id_factura, $ruta_fact);
            $pdf->generarPdf();
        } catch (\Throwable $e) {
            http_response_code(406);
            echo json_encode($e);
        }
        $params = array('id' => $id_factura, 'Ruta' => $ruta_fact);
        $url = $url . '?' . http_build_query($params);

        if ($respuesta_dian["Respuesta"] == 'Documento con errores en campos mandatorios.') {
            $respuesta_dian["Respuesta"] = 'Documento Validado por la Dian';
        }
        $respuesta["Respuesta_Correo"] = EnviarMail($cufe, $qr, $respuesta_dian["Respuesta"], $aplication_response);

        if ($respuesta["Respuesta_Correo"]["Estado"] == "Error") {
            http_response_code(424);
        }
        if ($tipo_factura == 'Factura') {
            $query = "SELECT IFNULL(PT.Id_Propharmacy, D.Id_Punto_Dispensacion) as Id_Punto_Dispensacion, P.Id_Regimen   FROM        Dispensacion D
                    Inner Join Punto_Dispensacion PT ON PT.Id_Punto_Dispensacion = D.Id_Punto_Dispensacion
                    INNER JOIN Factura F ON F.Id_Dispensacion = D.Id_Dispensacion
                    INNER JOIN Paciente P   ON P.Id_Paciente = D.Numero_Documento
                    WHERE F.Id_Factura=$id_factura";

            $oItem = new consulta();
            $oItem->setQuery($query);
            $dispensacion = $oItem->getData();

            $datos_movimiento_contable['Id_Registro'] = $id_factura;
            $datos_movimiento_contable['Nit'] = $factura['Id_Cliente'];
            $datos_movimiento_contable['Id_Regimen'] = $dispensacion['Id_Regimen'];
            $datos_movimiento_contable['Id_Punto_Dispensacion'] = $dispensacion['Id_Punto_Dispensacion'];
            $contabilizar->CrearMovimientoContable('Factura', $datos_movimiento_contable);
        }
    } else {
        // Cuando DIAN responde con error, revertir el estado de la dispensación
        if ($tipo_factura == 'Factura') {
            $query = "SELECT D.Id_Dispensacion FROM Dispensacion D
                    INNER JOIN Factura F ON F.Id_Dispensacion = D.Id_Dispensacion
                    WHERE F.Id_Factura=$id_factura";

            $oItem = new consulta();
            $oItem->setQuery($query);
            $dispensacion = $oItem->getData();
            unset($oItem);

            if ($dispensacion && isset($dispensacion['Id_Dispensacion'])) {
                // Revertir el estado de la dispensación a "Sin Facturar"
                $oItem = new complex("Dispensacion", "Id_Dispensacion", $dispensacion['Id_Dispensacion']);
                $oItem->Estado_Facturacion = "Sin Facturar";
                $oItem->Id_Factura = NULL;
                $oItem->Fecha_Facturado = NULL;
                $oItem->save();
                unset($oItem);
            }
        }

        // Actualizar el estado de la factura para indicar que hubo error
        $oItem = new complex($tipo_factura, "Id_" . $tipo_factura, $id_factura);
        $oItem->Procesada = "false";
        $oItem->save();
        unset($oItem);

        http_response_code(406);
    }

    $respuesta["Procesada"] = $respuesta_dian['Respuesta'];
    return ($respuesta);
}
function GetQr($cufe)
{
    global $factura, $totales, $configuracion;
    $fecha = str_replace(":", "", $factura['Fecha_Documento']);
    $fecha = str_replace("-", "", $fecha);
    $fecha = str_replace(" ", "", $fecha);

    $qr = "NumFac: " . $factura['Codigo'] . "\n";
    $qr .= "FecFac: " . $fecha . "\n";
    $qr .= "NitFac: " . getNit($configuracion) . "\n";
    $qr .= "DocAdq: " . $factura['Id_Cliente'] . "\n";
    $qr .= "ValFac: " . number_format($totales['Total'], 2, ".", "") . "\n";
    $qr .= "ValIva: " . number_format($totales['Total_Iva'], 2, ".", "") . "\n";
    $qr .= "ValOtroIm: 0.00 \n";
    $qr .= "ValFacIm: " . number_format(($totales['Total_Iva'] + $totales['Total']), 2, ".", "") . "\n";
    $qr .= "CUFE: " . $cufe . "\n";
    $qr = generarqrFE($qr);

    return ($qr);
}

function getNit()
{
    global $configuracion;
    $nit = explode("-", $configuracion['NIT']);
    $nit = str_replace(".", "", $nit[0]);
    return $nit;
}
function EnviarMail($cufe, $qr, $dian, $aplication_response)
{
    global $cliente, $factura, $resolucion;

    $destino = ((($cliente["Correo_Persona_Contacto"] != "" && $cliente["Correo_Persona_Contacto"] != "NULL") ? $cliente["Correo_Persona_Contacto"] : 'facturacionelectronicacont@prohsa.com'));

    // $destino="desarrollo.proh@gmail.com";
    // $destino="desarrollo.proh@gmail.com";
    $nit = getNit();
    $asunto = "$nit;PRODUCTOS HOSPITALARIOS S.A.;$factura[Codigo];01;PRO-H";


    if (strtolower($destino) == 'siifnacion.facturaelectronica@minhacienda.gov.co') {
        $asunto = str_replace('#$', '', $factura['Observaciones']);
        $asunto = "#$" . $asunto . "#$";
    }


    $contenido = GetHtmlFactura($dian);
    $xml = getXml($aplication_response, $cufe);
    $fact = getFact();

    $zip = new ZipArchive();
    $filename = '/home/sigesproph/api-dian.sigesproph.com.co/api-dian/storage/app/zip/1/' . $resolucion["resolution_id"] . '/z' . getNombre() . '.zip';

    if (file_exists($filename)) {
        unlink($filename);
    }

    if ($zip->open($filename, ZIPARCHIVE::CREATE) === true) {
        $zip->addFile($fact, "fv" . getNombre() . '.pdf');
        $zip->addFile($xml, "ad" . getNombre() . '.xml');
    }
    $zip->close();
    //var_dump($fact);
    $email = new EnviarCorreo();
    $respuesta = $email->EnviarFacturaDian($destino, $asunto, $contenido, $filename, "");
    // $respuesta = $fact;
    return ($respuesta);
}

function GetHtmlFactura($dian)
{
    global $factura, $cliente, $configuracion, $totales;
    $html = '<!doctype html>
            <html>
            <head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">
                <meta name="viewport" content="width=device-width" />

                <title>Facturación Electrónica - Productos Hospitalarios (Pro H) S.A</title>
                <style>
                    img{border:none;-ms-interpolation-mode:bicubic;max-width:100%}body{background-color:#f6f6f6;font-family:sans-serif;-webkit-font-smoothing:antialiased;font-size:14px;line-height:1.4;margin:0;padding:0;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%}.body{background-color:#f6f6f6;width:100%}.container{display:block;margin:0 auto!important;max-width:580px;padding:10px;width:580px}.content{box-sizing:border-box;display:block;margin:0 auto;max-width:580px;padding:10px}.main{background:#fff;border-radius:3px;width:100%}.wrapper{box-sizing:border-box;padding:20px}.content-block{padding-bottom:10px;padding-top:10px}.footer{clear:both;margin-top:10px;text-align:center;width:100%}.footer a,.footer p,.footer span,.footer td{color:#999;font-size:12px;text-align:center}h5{font-size:14px;font-weight:700;text-align:left;color:#3c5dc6}p{font-family:sans-serif;font-size:11px;font-weight:400;margin:0;margin-bottom:15px;text-align:justify}span{color:#000;font-family:sans-serif;font-weight:600}a{color:#3c5dc6;text-decoration:none}.logo{border:0;outline:0;text-decoration:none;display:block;text-align:center}.align-center{text-align:center!important}.preheader{color:transparent;display:none;height:0;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;visibility:hidden;width:0}.powered-by a{text-decoration:none;text-align:center!important}hr{border:0;border-bottom:1px solid #eeeef0;margin:8px 0}@media all{.ExternalClass{width:100%}.ExternalClass,.ExternalClass div,.ExternalClass font,.ExternalClass p,.ExternalClass span,.ExternalClass td{line-height:100%}.apple-link a{color:inherit!important;font-family:inherit!important;font-size:inherit!important;font-weight:inherit!important;line-height:inherit!important;text-decoration:none!important}#MessageViewBody a{color:inherit;text-decoration:none;font-size:inherit;font-family:inherit;font-weight:inherit;line-height:inherit}}
                </style>
            </head>

            <body class="">
                <span class="preheader">Factura Electronica ' . $factura["Codigo"] . '</span>
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="body">
                    <tr>
                        <td>&nbsp;</td>
                        <td class="container">
                            <div class="content">
                                <table role="presentatioran" class="main">
                                    <tr>
                                        <td class="wrapper">
                                            <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td>
                                                        <img alt="ProH" height="100" border="0" class="logo" src="https://sigesproph.com.co/assets/images/LogoProh.jpg" />
                                                        <hr>
                                                        <p>Estimado, <span>' . $cliente["Nombre"] . '</span></p>
                                                        <p>Ha recibido un documento electrónico generedo y enviado mediante el sistema de Facturación Electrónica de Productos Hospitalarios S.A. con la siguiente información:</p>
                                                        <hr>
                                                        <h5>Datos del Emisor</h5>
                                                        <hr>
                                                        <p><span>Nombre: </span>' . $configuracion["Nombre_Empresa"] . '</p>
                                                        <p><span>Identificación: </span>' . $configuracion["NIT"] . '</p>
                                                        <hr>
                                                        <h5>Información del Documento</h5>
                                                        <hr>
                                                        <p><span>Fecha: </span>' . $factura["Fecha_Documento"] . '</p>
                                                        <p><span>Tipo: Factura de Venta</span></p>
                                                        <p><span>Numero: </span>' . $factura["Codigo"] . '</p>
                                                        <p><span>Moneda: </span>COP</p>
                                                        <p><span>Valor Total: </span>$' . number_format(($totales["Total"] + $totales["Total_Iva"]), 2, ",", ".") . '</p>
                                                        <hr>
                                                        <h5>Respuesta de la DIAN</h5>
                                                        <hr>
                                                        <p>' . $dian . '</p>
                                                        <hr>
                                                        <p>Adjunto encontrará la representación gráfica del documento en formato PDF y el documento electrónico en formato XML.</p>
                                                        <p class="content-block powered-by">Nota: No responda este mensaje, ha sido enviado desde una dirección de correo electrónico no monitoreada.</p>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>

                                </table>

                                <div class="footer">
                                    <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td class="content-block">
                                                <span class="apple-link align-center">Productos Hospitalarios S.A</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="content-block powered-by align-center">
                                                Desarrollado por <a href="https://www.corvuslab.co/">Corvus Lab</a>.
                                            </td>
                                        </tr>
                                    </table>
                                </div>

                            </div>
                        </td>
                        <td>&nbsp;</td>
                    </tr>
                </table>
            </body>
            </html>';

    return ($html);
}

function getXml($aplication_response, $cufe)
{

    global $resolucion, $configuracion, $factura, $cliente;

    preg_match('/IssueDate>(.*?)<\/cbc:IssueDate/is', $aplication_response, $coincidencias);
    $fecha = $coincidencias[1];
    preg_match('/IssueTime>(.*?)<\/cbc:IssueTime/is', $aplication_response, $coincidencias2);
    $hora = $coincidencias2[1];
    preg_match('/ResponseCode>(.*?)<\/cbc:ResponseCode/is', $aplication_response, $coincidencias3);
    $respuesta = $coincidencias3[1];

    $name_file = getNombre();
    $xml_invoice = '/home/sigesproph/api-dian.sigesproph.com.co/api-dian/storage/app/xml/1/' . $resolucion["resolution_id"] . '/fv' . $name_file . '.xml';

    $xml_factura = file_get_contents($xml_invoice);

    $num = explode("-", $configuracion["NIT"]);
    $nit = str_replace(".", "", $num[0]);
    $dv = $num[1];

    $xml = '<?xml version="1.0" encoding="utf-8" standalone="no"?>
    <AttachedDocument xmlns="urn:oasis:names:specification:ubl:schema:xsd:AttachedDocument-2"
    xmlns:ds="http://www.w3.org/2000/09/xmldsig#"
    xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"
    xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2"
    xmlns:ccts="urn:un:unece:uncefact:data:specification:CoreComponentTypeSchemaModule:2"
    xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2"
    xmlns:xades="http://uri.etsi.org/01903/v1.3.2#"
    xmlns:xades141="http://uri.etsi.org/01903/v1.4.1#">
    <cbc:UBLVersionID>UBL 2.1</cbc:UBLVersionID>
    <cbc:CustomizationID>Documentos adjuntos</cbc:CustomizationID>
    <cbc:ProfileID>Factura ELectrónica de Venta</cbc:ProfileID>
    <cbc:ProfileExecutionID>1</cbc:ProfileExecutionID>
    <cbc:ID>' . $factura["Codigo"] . '</cbc:ID>
    <cbc:IssueDate>' . $fecha . '</cbc:IssueDate>
    <cbc:IssueTime>' . $hora . '</cbc:IssueTime>
    <cbc:DocumentType>Contenedor de Factura Electrónica</cbc:DocumentType>
    <cbc:ParentDocumentID>' . $factura["Codigo"] . '</cbc:ParentDocumentID>
    <cac:SenderParty>
    <cac:PartyTaxScheme>
    <cbc:RegistrationName>' . $configuracion["Nombre_Empresa"] . '</cbc:RegistrationName>
    <cbc:CompanyID schemeName="31" schemeID="' . $dv . '" schemeAgencyName="CO, DIAN (Dirección de Impuestos y Aduanas Nacionales)" schemeAgencyID="195">' . $nit . '</cbc:CompanyID>
    <cbc:TaxLevelCode listName="48">R-99-PN</cbc:TaxLevelCode>
    <cac:TaxScheme>
    <cbc:ID>ZA</cbc:ID>
    <cbc:Name>IVA e INC</cbc:Name>
    </cac:TaxScheme>
    </cac:PartyTaxScheme>
    </cac:SenderParty>
    <cac:ReceiverParty>
    <cac:PartyTaxScheme>
    <cbc:RegistrationName>' . $cliente["Nombre"] . '</cbc:RegistrationName>
    <cbc:CompanyID schemeName="31" schemeID="' . $cliente["Digito_Verificacion"] . '" schemeAgencyName="CO, DIAN (Dirección de Impuestos y Aduanas Nacionales)" schemeAgencyID="195">' . $cliente["Id_Cliente"] . '</cbc:CompanyID>
    <cbc:TaxLevelCode listName="48">R‐99‐PN</cbc:TaxLevelCode>
    <cac:TaxScheme/>
    </cac:PartyTaxScheme>
    </cac:ReceiverParty>
    <cac:Attachment>
    <cac:ExternalReference>
    <cbc:MimeCode>text/xml</cbc:MimeCode>
    <cbc:EncodingCode>UTF-8</cbc:EncodingCode>
    <cbc:Description><![CDATA[' .   str_replace("&#xF3;", "ó", $xml_factura) . ']]></cbc:Description>
    </cac:ExternalReference>
    </cac:Attachment>
    <cac:ParentDocumentLineReference>
    <cbc:LineID>1</cbc:LineID>
    <cac:DocumentReference>
    <cbc:ID>' . $factura["Codigo"] . '</cbc:ID>
    <cbc:UUID schemeName="CUFE-SHA384">' . $cufe . '</cbc:UUID>
    <cbc:IssueDate>' . $fecha . '</cbc:IssueDate>
    <cbc:DocumentType>ApplicationResponse</cbc:DocumentType>
    <cac:Attachment>
    <cac:ExternalReference>
    <cbc:MimeCode>text/xml</cbc:MimeCode>
    <cbc:EncodingCode>UTF-8</cbc:EncodingCode>
    <cbc:Description><![CDATA[' .  $aplication_response . ']]></cbc:Description>
    </cac:ExternalReference>
    </cac:Attachment>
    <cac:ResultOfVerification>
    <cbc:ValidatorID>Unidad Especial Dirección de Impuestos y Aduanas Nacionales</cbc:ValidatorID>
    <cbc:ValidationResultCode>' . $respuesta . '</cbc:ValidationResultCode>
    <cbc:ValidationDate>' . $fecha . '</cbc:ValidationDate>
    <cbc:ValidationTime>' . $hora . '</cbc:ValidationTime>
    </cac:ResultOfVerification>
    </cac:DocumentReference>
    </cac:ParentDocumentLineReference>
    </AttachedDocument>';

    file_put_contents('/home/sigesproph/api-dian.sigesproph.com.co/api-dian/storage/app/ad/1/' . $resolucion["resolution_id"] . '/ad' . $name_file . '.xml', $xml);

    $xml_resp = '/home/sigesproph/api-dian.sigesproph.com.co/api-dian/storage/app/ad/1/' . $resolucion["resolution_id"] . '/ad' . $name_file . '.xml';

    return ($xml_resp);
}

function getCliente()
{
    global $factura;

    /*   $query="SELECT C.*,
    (SELECT D.Nombre FROM Departamento D WHERE D.Id_Departamento=C.Id_Departamento) as Departamento,
    (SELECT M.Nombre FROM Municipio M WHERE M.Id_Municipio=C.Id_Municipio) as Ciudad
    FROM Cliente C WHERE C.Id_Cliente=".$this->factura['Id_Cliente']; */
    #correo_persona_contacto

    $query = 'SELECT "Cliente" AS Tipo_Tercero, Id_Cliente, Contribuyente, Autorretenedor,
                        (CASE
                            WHEN Tipo = "Juridico" THEN Razon_Social
                            ELSE  COALESCE(Nombre, CONCAT_WS(" ",Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido) )

                        END) AS Nombre,
                        Correo_Persona_Contacto,
                        Celular, Tipo, Tipo_Identificacion,
                        Digito_Verificacion, Regimen, Direccion, Telefono_Persona_Contacto AS Telefono,
            Id_Municipio, IFNULL(Condicion_Pago , 1 ) as Condicion_Pago
             FROM Cliente WHERE Id_Cliente =' . $factura['Id_Cliente'];
    $oCon = new consulta();
    $oCon->setQuery($query);
    $cliente = $oCon->getData();

    unset($oCon);
    return $cliente;
}
function getNombre()
{
    global $resolucion, $factura;
    $nit = getNit();
    $codigo = (int) str_replace($resolucion['Codigo'], "", $factura['Codigo']);
    $nombre = str_pad($nit, 10, "0", STR_PAD_LEFT) . "000" . date("y") . str_pad($codigo, 8, "0", STR_PAD_LEFT);
    return $nombre;
}

function getFact()
{
    global $resolucion;
    $fact = $_SERVER['DOCUMENT_ROOT'] . "/ARCHIVOS/FACTURA_ELECTRONICA_PDF/" . $resolucion["resolution_id"] . "/fv" . getNombre() . '.pdf';
    return ($fact);
}
