<?php
   require_once(__DIR__.'/../config/start.inc.php');
    require_once(__DIR__ . '/../vendor/autoload.php');
    include_once('class.lista.php');
    include_once('class.complex.php');
    include_once('class.consulta.php');
    require_once('class.qr.php'); 
     require_once('class.php_mailer.php'); 
    
    require_once(__DIR__.'/helper/factura_elec_dis_helper.php');
    
    use GuzzleHttp\Client;
    use GuzzleHttp\HandlerStack;
    use GuzzleHttp\Handler\CurlHandler;
    use PhpParser\Node\Expr\Exit_;

class FacturaElectronica
{
    private $resolucion = '', $factura = '', $configuracion = '', $productos = [], $cliente = '', $totales = '', $tipo_factura = '', $id_factura = '';
    private $sector_salud_activo = false;
    private $periodo_facturacion_inicio = '';
    private $periodo_facturacion_fin = '';

    function __construct($tipo_factura, $id_factura, $resolucion_facturacion, $sector_salud_config = null)
    {
        $this->tipo_factura = $tipo_factura;
        $this->id_factura = $id_factura;
        if (is_array($sector_salud_config)) {
            $this->sector_salud_activo = !empty($sector_salud_config['activo']);
            $this->periodo_facturacion_inicio = trim($sector_salud_config['fecha_inicio'] ?? '');
            $this->periodo_facturacion_fin = trim($sector_salud_config['fecha_fin'] ?? '');
        }
        self::getDatos($tipo_factura, $id_factura, $resolucion_facturacion);

        // Logging desactivado por rendimiento
    }

    function __destruct()
    {
        return; // logging desactivado por rendimiento

        if (self::$logFile === null) {
            return;
        }

        try {
            $fecha = date('Y-m-d H:i:s');
            $linea = "[{$fecha}] {$contexto}";

            if (!empty($datos)) {
                $linea .= ' | ' . json_encode($datos, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
            }

            file_put_contents(self::$logFile, $linea . PHP_EOL, FILE_APPEND);
        } catch (\Exception $e) {
            // Silenciar errores de logging para no interrumpir el flujo principal
        }
    }

    function GenerarFactura()
    {
        $datos = $this->GeneraJson($this->tipo_factura);
        $respuesta_dian = $this->GetApi($datos);

        $aplication_response = '';
        if (isset($respuesta_dian["Json"]["ResponseDian"]["Envelope"]["Body"]["SendBillSyncResponse"]["SendBillSyncResult"]["XmlBase64Bytes"])) {
            $aplication_response = $respuesta_dian["Json"]["ResponseDian"]["Envelope"]["Body"]["SendBillSyncResponse"]["SendBillSyncResult"]["XmlBase64Bytes"];
            $aplication_response = base64_decode($aplication_response);
        }
        
        $email = $datos['customer']['email'];
        $cufe = isset($respuesta_dian["Cufe"]) ? $respuesta_dian["Cufe"] : '';
        if ($cufe === '') {
            $cufe = $this->getCUFE();
        }
        $qr = $cufe !== '' ? $this->GetQr($cufe) : '';
        $mensaje_respuesta = isset($respuesta_dian["Respuesta"]) ? $respuesta_dian["Respuesta"] : '';
        if (!is_string($mensaje_respuesta)) {
            $mensaje_respuesta = json_encode($mensaje_respuesta);
        }
        if (
            stripos($mensaje_respuesta, "procesado anteriormente") !== false
            || stripos($mensaje_respuesta, "archivo existente") !== false
        ) {
            $estado = "true";
        } else {
            $estado = isset($respuesta_dian["Procesada"]) ? $respuesta_dian["Procesada"] : "false";
        }
        if (($respuesta_dian["Estado"] ?? '') === "error") {
            $estado = "false";
        }

        if ($estado == "true") {

            $oItem = new complex($this->tipo_factura, "Id_" . $this->tipo_factura, $this->id_factura);
            $oItem->Cufe = $cufe;
            $oItem->Codigo_Qr = $qr;
            $oItem->Procesada = $estado;
            $oItem->save();
            unset($oItem);

            $nit = $this->getNit();
            $ruta_nueva = $_SERVER['DOCUMENT_ROOT'] . "/ARCHIVOS/FACTURA_ELECTRONICA_PDF/" . $this->resolucion["resolution_id"];


            if (!file_exists($ruta_nueva)) {
                mkdir($ruta_nueva, 0777, true);
            }

            $nombre_factura = "fv" . $this->getNombre() . ".pdf";
            $ruta_fact =  $ruta_nueva . "/" . $nombre_factura;



            
            if ($this->tipo_factura == "Factura") {
                $this->GenerarPdfRemoto('https://sigesproph.com.co/php/facturacion_electronica/factura_np_pdf.php', $ruta_fact);
            } elseif ($this->tipo_factura == "Factura_Venta") {
                $this->GenerarPdfRemoto('https://sigesproph.com.co/php/facturacion_electronica/factura_venta_pdf.php', $ruta_fact);
            } elseif ($this->tipo_factura == "Factura_Capita") {
                $this->GenerarPdfRemoto('https://sigesproph.com.co/php/facturacion_electronica/factura_capita_pdf.php', $ruta_fact);
            } elseif ($this->tipo_factura == "Factura_Administrativa") {
                $this->GenerarPdfRemoto('https://sigesproph.com.co/php/facturacion_electronica/factura_administrativa_pdf.php', $ruta_fact);
            }
        }
        
        $respuesta["Json"] = is_array($respuesta_dian) ? ($respuesta_dian["Json"] ?? null) : null;
        $respuesta["Enviado"] = is_array($respuesta_dian) ? ($respuesta_dian["Enviado"] ?? null) : null;
        




        //$this->getXml($aplication_response,$cufe); // AUGUSTO 11 JULIO 2024
        //$this->EnviarMail($cufe, $qr, $respuesta_dian["Respuesta"],$aplication_response); // TEMPORAL VALIDACION AUGUSTO + ROBERTH 1 SEP 2021
        
        if ($estado !== "true") {

            $respuesta["Estado"] = "Error";
            $respuesta["Detalles"] = $respuesta_dian["Respuesta"];
            $data["Cufe"] = $cufe;
            $data["Qr"] = $qr;
            $respuesta["Datos"] = $data;
        } elseif ($estado == "true") {
            
            $respuesta["Respuesta_Correo"] = $this->EnviarMail($cufe, $qr, $respuesta_dian["Respuesta"],$aplication_response,$email); // "CORREO NO ENVIADO"; //

            $respuesta["Estado"] = "Exito";

            $respuesta["Detalles"] = $respuesta_dian["Respuesta"];
            $data["Cufe"] = $cufe;
            $data["Qr"] = $qr;
            $respuesta["Datos"] = $data;
        }
        return ($respuesta);
        }
    
    private function GetMunicipio($idMunicipio)
    {
        $query = 'SELECT municipalities_id FROM Municipio WHERE Id_Municipio = ' . $idMunicipio;
        $oCon = new consulta();
        $oCon->setQuery($query);
        $mun = $oCon->getData();
        return $mun['municipalities_id'];
    }
    private function EnviarMail($cufe, $qr, $dian, $aplication_response,$email)
{
    $destino = (($email != "" && $email != "NULL") ? $email : "facturacionelectronicacont@prohsa.com");
    
    $asunto = "Su Factura Electrónica: " . $this->factura["Codigo"];
    $contenido = $this->GetHtmlFactura($dian);
    $fact = $this->getFact();
    

    $xmlContent = $aplication_response;

    // Crear el archivo ZIP
    $zip = new ZipArchive();
    $tmpDir = sys_get_temp_dir();
    $zipFileName = rtrim($tmpDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'factura_' . $this->factura["Codigo"] . '.zip';

    if ($zip->open($zipFileName, ZipArchive::CREATE) !== TRUE) {
        exit("No se pudo crear el archivo ZIP.");
    }

    // Agregar el archivo XML al ZIP
    if ($xmlContent != '') {
        $tempXml = tmpfile();  // Crea un archivo temporal para el XML
        fwrite($tempXml, $xmlContent);
        $tempXmlPath = stream_get_meta_data($tempXml)['uri'];
        $zip->addFile($tempXmlPath, 'factura.xml');
    }

    // Agregar el archivo PDF al ZIP
    if ($fact != '') {
        $zip->addFile($fact, 'factura.pdf');
    }

    $zip->close();

    $email = new EnviarCorreo('facturacion');
    $respuesta = $email->EnviarFacturaDian($destino, $asunto, $contenido, $zipFileName);

    if (file_exists($zipFileName)) {
        unlink($zipFileName);
    }

    return $respuesta;
}

    public function ReenviarCorreoExistente($email_override = '')
    {
        $destino = $email_override;
        if ($destino === '') {
            $correo_cliente = isset($this->cliente["Correo_Persona_Contacto"]) ? $this->cliente["Correo_Persona_Contacto"] : '';
            $destino = (($correo_cliente != "" && $correo_cliente != "NULL") ? $correo_cliente : "facturacionelectronicacont@prohsa.com");
        }

        $cufe = '';
        if (is_array($this->factura) && isset($this->factura["Cufe"])) {
            $cufe = $this->factura["Cufe"];
        }
        if ($cufe === '') {
            $cufe = $this->getCUFE();
        }
        $qr = $cufe !== '' ? $this->GetQr($cufe) : '';

        $pdf_path = $this->getFact();
        if (!file_exists($pdf_path)) {
            if ($this->tipo_factura == "Factura") {
                $this->GenerarPdfRemoto('https://sigesproph.com.co/php/facturacion_electronica/factura_np_pdf.php', $pdf_path);
            } elseif ($this->tipo_factura == "Factura_Venta") {
                $this->GenerarPdfRemoto('https://sigesproph.com.co/php/facturacion_electronica/factura_venta_pdf.php', $pdf_path);
            } elseif ($this->tipo_factura == "Factura_Capita") {
                $this->GenerarPdfRemoto('https://sigesproph.com.co/php/facturacion_electronica/factura_capita_pdf.php', $pdf_path);
            } elseif ($this->tipo_factura == "Factura_Administrativa") {
                $this->GenerarPdfRemoto('https://sigesproph.com.co/php/facturacion_electronica/factura_administrativa_pdf.php', $pdf_path);
            }
        }

        $dian_msg = "Documento electrónico reenviado.";

        return $this->EnviarMail($cufe, $qr, $dian_msg, '', $destino);
    }

    private function GenerarPdfRemoto($urlBase, $ruta_fact)
    {
        $query = http_build_query(
            ['id' => $this->id_factura, 'Ruta' => $ruta_fact],
            '',
            '&',
            PHP_QUERY_RFC3986
        );
        $url = $urlBase . '?' . $query;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_exec($ch);
        curl_close($ch);
    }

    private function GetHtmlFactura($dian)
    {
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
                <span class="preheader">Factura Electronica ' . $this->factura["Codigo"] . '</span>
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
                                                        <p>Estimado, <span>' . $this->cliente["Nombre"] . '</span></p>
                                                        <p>Ha recibido un documento electrónico generedo y enviado mediante el sistema de Facturación Electrónica de Productos Hospitalarios S.A. con la siguiente información:</p>
                                                        <hr>
                                                        <h5>Datos del Emisor</h5>
                                                        <hr>
                                                        <p><span>Nombre: </span>' . $this->configuracion["Nombre_Empresa"] . '</p>
                                                        <p><span>Identificación: </span>' . $this->configuracion["NIT"] . '</p>
                                                        <hr>
                                                        <h5>Información del Documento</h5>
                                                        <hr>
                                                        <p><span>Fecha: </span>' . $this->factura["Fecha_Documento"] . '</p>
                                                        <p><span>Tipo: Factura de Venta</span></p>
                                                        <p><span>Numero: </span>' . $this->factura["Codigo"] . '</p>
                                                        <p><span>Moneda: </span>COP</p>
                                                        <p><span>Valor Total: </span>$' . number_format(($this->totales["Total"] + $this->totales["Total_Iva"]), 2, ",", ".") . '</p>
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
    private function getXml($aplication_response,$cufe)
    {
        preg_match('/IssueDate>(.*?)<\/cbc:IssueDate/is', $aplication_response, $coincidencias);
        $fecha = $coincidencias[1];
        preg_match('/IssueTime>(.*?)<\/cbc:IssueTime/is', $aplication_response, $coincidencias2);
        $hora = $coincidencias2[1];
        preg_match('/ResponseCode>(.*?)<\/cbc:ResponseCode/is', $aplication_response, $coincidencias3);
        $respuesta = $coincidencias3[1];
        
        $name_file=$this->getNombre();
        $xml_invoice = 'https://api-dian.innovating.com.co/api-dian/storage/app/xml/1/' . $this->resolucion["resolution_id"] . '/fv' . $name_file . '.xml';
        
        $xml_factura = file_get_contents($xml_invoice);
        
        $num = explode("-",$this->configuracion["NIT"]);
        $nit = str_replace(".","",$num[0]);
        $dv= $num[1];
        
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
        <cbc:ID>'.$this->factura["Codigo"] .'</cbc:ID>
        <cbc:IssueDate>'.$fecha.'</cbc:IssueDate>
        <cbc:IssueTime>'.$hora.'</cbc:IssueTime>
        <cbc:DocumentType>Contenedor de Factura Electrónica</cbc:DocumentType>
        <cbc:ParentDocumentID>'.$this->factura["Codigo"] .'</cbc:ParentDocumentID>
        <cac:SenderParty>
        <cac:PartyTaxScheme>
        <cbc:RegistrationName>'.$this->configuracion["Nombre_Empresa"].'</cbc:RegistrationName>
        <cbc:CompanyID schemeName="31" schemeID="'.$dv.'" schemeAgencyName="CO, DIAN (Dirección de Impuestos y Aduanas Nacionales)" schemeAgencyID="195">'.$nit.'</cbc:CompanyID>
        <cbc:TaxLevelCode listName="48">R-99-PN</cbc:TaxLevelCode>
        <cac:TaxScheme>
        <cbc:ID>01</cbc:ID>
        <cbc:Name>IVA</cbc:Name>
        </cac:TaxScheme>
        </cac:PartyTaxScheme>
        </cac:SenderParty>
        <cac:ReceiverParty>
        <cac:PartyTaxScheme>
        <cbc:RegistrationName>' . $this->cliente["Nombre"] . '</cbc:RegistrationName>
        <cbc:CompanyID schemeName="31" schemeID="'.$this->cliente["Digito_Verificacion"].'" schemeAgencyName="CO, DIAN (Dirección de Impuestos y Aduanas Nacionales)" schemeAgencyID="195">'.$this->cliente["Id_Cliente"].'</cbc:CompanyID>
        <cbc:TaxLevelCode listName="48">R‐99‐PN</cbc:TaxLevelCode>
        <cac:TaxScheme/>
        </cac:PartyTaxScheme>
        </cac:ReceiverParty>
        <cac:Attachment>
        <cac:ExternalReference>
        <cbc:MimeCode>text/xml</cbc:MimeCode>
        <cbc:EncodingCode>UTF-8</cbc:EncodingCode>
        <cbc:Description><![CDATA['.str_replace("&#xF3;","ó",$xml_factura).']]></cbc:Description>
        </cac:ExternalReference>
        </cac:Attachment>
        <cac:ParentDocumentLineReference>
        <cbc:LineID>1</cbc:LineID>
        <cac:DocumentReference>
        <cbc:ID>'.$this->factura["Codigo"] .'</cbc:ID>
        <cbc:UUID schemeName="CUFE-SHA384">'.$cufe.'</cbc:UUID>
        <cbc:IssueDate>'.$fecha.'</cbc:IssueDate>
        <cbc:DocumentType>ApplicationResponse</cbc:DocumentType>
        <cac:Attachment>
        <cac:ExternalReference>
        <cbc:MimeCode>text/xml</cbc:MimeCode>
        <cbc:EncodingCode>UTF-8</cbc:EncodingCode>
        <cbc:Description><![CDATA['.str_replace("  "," ",$aplication_response).']]></cbc:Description>
        </cac:ExternalReference>
        </cac:Attachment>
        <cac:ResultOfVerification>
        <cbc:ValidatorID>Unidad Especial Dirección de Impuestos y Aduanas Nacionales</cbc:ValidatorID>
        <cbc:ValidationResultCode>'.$respuesta.'</cbc:ValidationResultCode>
        <cbc:ValidationDate>'.$fecha.'</cbc:ValidationDate>
        <cbc:ValidationTime>'.$hora.'</cbc:ValidationTime>
        </cac:ResultOfVerification>
        </cac:DocumentReference>
        </cac:ParentDocumentLineReference>
        </AttachedDocument>';

        file_put_contents('/home/sigespro/api-dian.sigesproph.com.co/api-dian/storage/app/ad/1/' . $this->resolucion["resolution_id"] . '/ad' . $name_file . '.xml', $xml);
        
        $xml_resp='/home/sigespro/api-dian.sigesproph.com.co/api-dian/storage/app/ad/1/' . $this->resolucion["resolution_id"] . '/ad' . $name_file . '.xml';
 
  
        return ($xml_resp);
    }
    private function getFact()
    {
        $fact =  $_SERVER['DOCUMENT_ROOT'] . "/ARCHIVOS/FACTURA_ELECTRONICA_PDF/" . $this->resolucion["resolution_id"] . "/fv" . $this->getNombre() . '.pdf';

        return ($fact);
    }
    private function GetApi($datos)
    {

        $login = 'facturacion@prohsa.com';
        $password = '804016084';
        $host = 'https://api-dian.sigesproph.com.co'; 
        //$host = "http://192.168.40.201:443";
        //$host = "https:/api-dian.innovating.com.co";
        $api = '/api';
        $version = '/ubl2.1';
        $modulo = '/invoice';
        $url = $host . $api . $version . $modulo;

       
        $data = json_encode($datos);
 
        $ch = curl_init($url);
        
        curl_setopt($ch, CURLOPT_SSLVERSION, 4);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data); 
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);  
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        if (defined('FE_DEBUG_DIAN') && FE_DEBUG_DIAN) {
            curl_setopt($ch, CURLOPT_VERBOSE, true);
        } else {
            $curlStderr = fopen('php://temp', 'w+');
            curl_setopt($ch, CURLOPT_VERBOSE, false);
            curl_setopt($ch, CURLOPT_STDERR, $curlStderr);
        }
        
        
        
        //var_dump(base64_encode($login . ':' . $password));exit;
        $headers = array(
            "Content-type: application/json",
            "Accept: application/json",
            "Cache-Control: no-cache",
            "Authorization: Basic " . base64_encode($login . ':' . $password),
            "Pragma: no-cache",
            "SOAPAction:\"" . $url . "\"",
            "Content-length: " . strlen($data),
        );

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

       
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // $test= curl_getinfo($ch);
        //var_dump($result);
        /**/
       // $errno = curl_errno($ch);
        //$error_message = curl_strerror($errno);
        //echo "cURL error ({$errno}):\n {$error_message}";
        /**/

        //var_dump([curl_error($ch), curl_errno($ch), $result, curl_getinfo($ch, CURLINFO_HTTP_CODE)]);
       // exit;
        
        $sanearRespuesta = function ($texto) {
            if (!is_string($texto)) {
                return $texto;
            }
            $texto = str_ireplace(["<br />", "<br/>", "<br>"], " - ", $texto);
            $texto = strip_tags($texto);
            $texto = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $texto = preg_replace('/\s+/', ' ', $texto);
            return trim($texto, " -\t\n\r\0\x0B");
        };

        if (curl_errno($ch)) {
            $respuesta["Estado"] = "error";
            $respuesta["Error"] = '# Error : ' . curl_error($ch);
            return  $respuesta;
        } elseif ($result) {
            $json_output = json_decode($result, true);
            if (!is_array($json_output)) {
                $respuesta["Estado"] = "error";
                $respuesta["Procesada"] = "false";
                $respuesta["Respuesta"] = "Error al decodificar respuesta de la API DIAN (HTTP " . $httpCode . "): " . $sanearRespuesta($result);
                if (defined('FE_DEBUG_DIAN') && FE_DEBUG_DIAN) {
                    $respuesta["HttpCode"] = $httpCode;
                    $respuesta["Raw"] = $result;
                }
                return $respuesta;
            }

            //var_dump($json_output);
            $mensaje = isset($json_output["message"]) ? $json_output["message"] : '';
            $respuesta["Cufe"] = isset($json_output["cufe"]) ? $json_output["cufe"] : null;
            $respuesta["Json"] = $json_output;
            $respuesta["Enviado"] = $datos;

            $mensaje_saneado = $sanearRespuesta($mensaje);
            if ($mensaje !== '' && strpos($mensaje, "invalid") !== false) {
                $respuesta["Estado"] = "error";
                $respuesta["Respuesta"] = $sanearRespuesta(is_array($json_output["errors"]) ? implode(" - ", $json_output["errors"]) : $json_output["errors"]);
            } elseif (!isset($json_output["ResponseDian"]["Envelope"]["Body"]["SendBillSyncResponse"]["SendBillSyncResult"])) {
                $respuesta["Estado"] = "error";
                $respuesta["Procesada"] = "false";
                $respuesta["Respuesta"] = $mensaje_saneado !== '' ? $mensaje_saneado : "Respuesta DIAN inválida";
                if (
                    stripos($mensaje_saneado, "procesado anteriormente") !== false
                    || stripos($mensaje_saneado, "archivo existente") !== false
                ) {
                    $respuesta["Estado"] = "exito";
                    $respuesta["Procesada"] = "true";
                }
            } else {
                $r = $json_output["ResponseDian"]["Envelope"]["Body"]["SendBillSyncResponse"]["SendBillSyncResult"];
                $estado = $r["IsValid"];

                $respuesta["Procesada"] = $estado;
                if ($estado == "true") {
                    $respuesta["Estado"] = "exito";
                    $respuesta["Respuesta"] = $sanearRespuesta($r["StatusDescription"] . " - " . $r["StatusMessage"]);
                } else {
                    $respuesta["Estado"] = "error";
                    $mensajes = [];
                    foreach ((array) $r["ErrorMessage"] as $e) {
                        if (is_array($e)) {
                            $mensajes[] = implode(" - ", array_filter($e));
                        } else {
                            $mensajes[] = $e;
                        }
                    }
                    if (!empty($r["StatusMessage"])) {
                        $mensajes[] = $r["StatusMessage"];
                    }
                    if (!empty($r["StatusDescription"])) {
                        $mensajes[] = $r["StatusDescription"];
                    }
                    $respuesta["Respuesta"] = $sanearRespuesta(implode(" - ", array_filter($mensajes)));
                }
            }

            return $respuesta;
        }
    }

    private function ConsultaRespuesta($zipkey)
    {
        $login = 'facturacion@prohsa.com';
        $password = '804016084';
        $host = 'https://api-dian.innovating.com.co';
        $api = '/api';
        $version = '/ubl2.1';
        $modulo = '/status/zip/';
        $datos = [];
        $url = $host . $api . $version . $modulo . $zipkey;

        $data = json_encode($datos);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        $headers = array(
            "Content-type: application/json",
            "Accept: application/json",
            "Cache-Control: no-cache",
            "Authorization: Basic " . base64_encode($login . ':' . $password),
            "Pragma: no-cache",
            "SOAPAction:\"" . $url . "\""
        );

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            $respuesta["Estado"] = "error";
            $respuesta["Error"] = '# Error : ' . curl_error($ch);
            return  $respuesta;
        }
        if ($result) {
            $resp = json_encode($result);
            $json_output = json_decode($resp, true);
            $json_output = (array) json_decode($json_output, true);

            //var_dump($json_output);

            $mensaje = $json_output["message"];

            if (strpos($mensaje, "invalid") !== false) {
                $respuesta["Estado"] = "error";
                $respuesta["Error"] = $json_output["errors"];
            } else {

                if (isset($json_output["ResponseDian"]["Envelope"]["Body"]["GetStatusZipResponse"]["GetStatusZipResult"]["DianResponse"][0])) {
                    $respu["IsValid"] = $json_output["ResponseDian"]["Envelope"]["Body"]["GetStatusZipResponse"]["GetStatusZipResult"]["DianResponse"][0]["IsValid"];
                    $respu["StatusCode"] = $json_output["ResponseDian"]["Envelope"]["Body"]["GetStatusZipResponse"]["GetStatusZipResult"]["DianResponse"][0]["StatusCode"];
                    $respu["StatusDescription"] = $json_output["ResponseDian"]["Envelope"]["Body"]["GetStatusZipResponse"]["GetStatusZipResult"]["DianResponse"][0]["StatusDescription"];
                } else {
                    $respu["IsValid"] = $json_output["ResponseDian"]["Envelope"]["Body"]["GetStatusZipResponse"]["GetStatusZipResult"]["DianResponse"]["IsValid"];
                    $respu["StatusCode"] = $json_output["ResponseDian"]["Envelope"]["Body"]["GetStatusZipResponse"]["GetStatusZipResult"]["DianResponse"]["StatusCode"];
                    $respu["StatusDescription"] = $json_output["ResponseDian"]["Envelope"]["Body"]["GetStatusZipResponse"]["GetStatusZipResult"]["DianResponse"]["StatusDescription"];
                }
                $respuesta["Estado"] = "exito";
                $respuesta["Respuesta"] = $respu;
            }

            return $respuesta;
        }
    }

    private function GeneraJson($tipo_factura)
    {

        $resultado["cufe_propio"] = $this->getCufe();
        $resultado["number"] = (int)str_replace($this->resolucion['Codigo'], "", $this->factura['Codigo']);;
        $resultado["type_document_id"] = 1;
        $resultado["resolution_id"] = $this->resolucion["resolution_id"];
        if (!empty($this->resolucion["Id_Software"])) {
            $resultado["software_id"] = $this->resolucion["Id_Software"];
        }
        if (isset($this->resolucion["Pin"]) && $this->resolucion["Pin"] !== '' && $this->resolucion["Pin"] !== '1') {
            $resultado["software_pin"] = $this->resolucion["Pin"];
        }
        $fechaDocumento = $this->factura["Fecha_Documento"];
        if (defined('FE_USAR_FECHA_ACTUAL') && FE_USAR_FECHA_ACTUAL) {
            $fechaDocumento = date("Y-m-d H:i:s");
        }
        $resultado["date"] = date("Y-m-d", strtotime($fechaDocumento));
        $resultado["time"] = date("H:i:s", strtotime($fechaDocumento));
        //$resultado["send"]=true;
        $resultado["file"] = $this->getNombre();

        //nuevo
        
        $cliente['municipality_id'] =  (int)$this->GetMunicipio($this->cliente['Id_Municipio']);
        $cliente['country_id'] = 46;

        $cliente["identification_number"] = $this->cliente["Id_Cliente"];
        //$cliente["dv"]=$this->cliente["Id_Cliente"];

        $cliente["name"] = trim($this->cliente["Nombre"]);
        $cliente["phone"] = (($this->cliente["Telefono"] != "" && $this->cliente["Telefono"] != "NULL") ? trim($this->cliente["Telefono"]) : "0000000");
        // $cliente["phone"]="0000000";

        $cliente["type_organization_id"] = (($this->cliente["Tipo"] == "Juridico") ? 1 : 2); /* Juridica 1 - Natural 2*/
        $cliente["type_document_identification_id"] = (($this->cliente["Tipo_Identificacion"] == "NIT") ? 6 : 3); /* 6 NIT - 3 Cedula */

        if ($this->cliente["Tipo_Identificacion"] == "NIT") {
            $cliente["dv"] = $this->cliente["Digito_Verificacion"];
        }

        $cliente["type_regime_id"] = (($this->cliente["Regimen"] == "Comun") ? 2 : 1); /* 1 Simplificado - 2 Comun */
        // $cliente["type_liability_id"]=2;
        //

        $cliente["type_liability_id"] = 122;

        if ($this->cliente["Contribuyente"] == "Si") {
            $cliente["type_liability_id"] = 118;
        }

        if ($this->cliente["Regimen"] == "Simplificado") {
            $cliente["type_liability_id"] = 121;
        }


        if ($this->cliente["Autorretenedor"] == "Si") {
            $cliente["type_liability_id"] = 119;
        }


        $cliente["address"] = trim((($this->cliente["Direccion"] != "" && $this->cliente["Direccion"] != "NULL") ? trim($this->cliente["Direccion"]) : "SIN DIRECCION"));
        $cliente["email"] = trim((($this->cliente["Correo_Persona_Contacto"] != "" && $this->cliente["Correo_Persona_Contacto"] != "NULL") ? trim($this->cliente["Correo_Persona_Contacto"]) : "facturacionelectronica@prohsa.com"));
        $cliente["merchant_registration"] = "No Tiene";

        // Valores predeterminados para evitar notices durante la construcción del payload.
        $condicionPago = (isset($this->factura['Condicion_Pago']) && is_numeric($this->factura['Condicion_Pago']))
            ? (int) $this->factura['Condicion_Pago']
            : 1;
        $fechaPago = !empty($this->factura['Fecha_Pago'])
            ? $this->factura['Fecha_Pago']
            : date('Y-m-d');

        //NUEVO 
        $metodo_pago = [];
        //contado 2 efectivo 1
        $metodo_pago['payment_form_id'] = $condicionPago > 1 ?  2  : 1;

        $metodo_pago['payment_method_id'] = $condicionPago > 1 ?  30  : 31;
        $metodo_pago['payment_due_date'] = $fechaPago;

        $metodo_pago['duration_measure'] = $condicionPago;

        $resultado["customer"] = $cliente;

        $finales["line_extension_amount"] = number_format($this->totales["Total"], 2, ".", "");
        $finales["tax_exclusive_amount"] = number_format($this->totales["Total"], 2, ".", "");
        $finales["tax_inclusive_amount"] = number_format($this->totales["Total"] + $this->totales["Total_Iva"], 2, ".", "");
        $finales["allowance_total_amount"] = number_format($this->totales["Descuento"], 2, ".", "");
        $finales["charge_total_amount"] = 0;
        $finales["payable_amount"] = number_format($this->totales["Total"], 2, ".", "") + number_format($this->totales["Total_Iva"], 2, ".", "") - number_format($this->totales["Descuento"], 2, ".", "");

        $resultado["legal_monetary_totals"] = $finales;
        $j = -1;
        $produstos_finales = [];
        $base_imp = 0;
        $tot_imp = 0;
        
        $base_imp2 = 0;
        $tot_imp2 = 0;

        $base_des = 0;
        $tot_des = 0;
        
        
        $descue = [];
        foreach ($this->productos as $pro) {
            $j++;

            $descuento = $pro["Cantidad"] * $pro["Descuento"];

            if ($tipo_factura == "Factura_Venta") {
                $tot = $pro["Cantidad"] * $pro["Precio_Venta"];
                $precio = $pro["Precio_Venta"];
            } else {
                $tot = $pro["Cantidad"] * $pro["Precio"];
                $precio = $pro["Precio"];
            }

            $descuentos[0]["charge_indicator"] = false;
            $descuentos[0]["allowance_charge_reason"] = 'Discount';
            $descuentos[0]["amount"] = number_format($descuento, 2, ".", "");
            $descuentos[0]["base_amount"] = number_format($tot, 2, ".", "");

            if ($descuento > 0) {
                $base_des += $tot;
                $tot_des += $descuento;

                $descue[$j]["discount_id"] = ($j + 1);
                $descue[$j]["charge_indicator"] = false;
                $descue[$j]["allowance_charge_reason"] = 'Discount';
                $descue[$j]["amount"] = number_format($descuento, 2, ".", "");
                $descue[$j]["base_amount"] = number_format($tot, 2, ".", "");
            }

            $imp = $tot * $pro["Impuesto"] / 100;
            if ($imp > 0) {
                $base_imp += $tot;
                $tot_imp += $imp;
            }else{
                $base_imp2 += $tot;
                $tot_imp2 += $imp;
            }

            $impuestos[0]["tax_id"] = 1;
            $impuestos[0]["tax_amount"] = number_format($imp, 2, ".", "");
            $impuestos[0]["taxable_amount"] = number_format($tot, 2, ".", "");
            $impuestos[0]["percent"] = $pro["Impuesto"];


            $productos_finales[$j]["unit_measure_id"] = 70;
            $productos_finales[$j]["invoiced_quantity"] = $pro["Cantidad"];
            $productos_finales[$j]["line_extension_amount"] = number_format($tot, 2, ".", "");

            $productos_finales[$j]["free_of_charge_indicator"] = false;
            $productos_finales[$j]["reference_price_id"] = 1;

            if ((int)$precio == 0) {
                $productos_finales[$j]["free_of_charge_indicator"] = true;
                $productos_finales[$j]["reference_price_id"] = 1;
                $productos_finales[$j]["price_amount"] = number_format(1, 2, ".", "");
                // $referencia["AlternativeConditionPrice"]=;
                // $productos_finales[$j]["PricingReference"]=; 

            } else {
                $productos_finales[$j]["free_of_charge_indicator"] = false;
                $productos_finales[$j]["reference_price_id"] = 1;
                $productos_finales[$j]["price_amount"] = number_format($precio, 2, ".", "");
            }

            $productos_finales[$j]["allowance_charges"] = $descuentos;


            $productos_finales[$j]["tax_totals"] = $impuestos;

            $productos_finales[$j]["description"] = trim($pro["Producto"]);
            $productos_finales[$j]["code"] = trim($pro["CUM"]);
            $productos_finales[$j]["type_item_identification_id"] = 3;

            $productos_finales[$j]["base_quantity"] = $pro["Cantidad"];
        }

        if($tot_imp>0){
            $primero["tax_id"] = 1;
            $primero["tax_amount"] = number_format($tot_imp, 2, ".", "");
            $primero["taxable_amount"] = number_format($base_imp, 2, ".", "");
            $primero["percent"] = "19";
            
            $impues[]=$primero;
        }
        
        if($base_imp2>0){
            $segundo["tax_id"] = 1;
            $segundo["tax_amount"] = number_format($tot_imp2, 2, ".", "");
            $segundo["taxable_amount"] = number_format($base_imp2, 2, ".", "");
            $segundo["percent"] = "0";
            
            $impues[]=$segundo;
        }
        
        

        /*$descue[0]["charge_indicator"]=false;
                $descue[0]["discount_id"]=1;
                $descue[0]["allowance_charge_reason"]='Discount';
                $descue[0]["amount"]=number_format($tot_des,0,".","");
                $descue[0]["base_amount"]=number_format($base_des,0,".","");*/

        if ($this->sector_salud_activo) {
            $healt_sector_data = $this->getDataDis();
            if (is_array($healt_sector_data)) {
                $healt_sector_data['Fecha_Inicio_Periodo_Facturacion'] = $this->periodo_facturacion_inicio;
                $healt_sector_data['Fecha_Fin_Periodo_Facturacion'] = $this->periodo_facturacion_fin;
                $resultado["healt_sector"] = $healt_sector_data;
            }
        }
        $resultado["tax_totals"] = $impues;
        $resultado["allowance_charges"] = $descue;
        $resultado["invoice_lines"] = $productos_finales;
        $resultado["payment_form"] = $metodo_pago;
        //var_dump($resultado);
        //exit;
        return ($resultado);
    }

    public function ObtenerJsonSolicitud()
    {
        return $this->GeneraJson($this->tipo_factura);
    }

    private function getCUFE()
    {
        $nit = self::getNit();
        $fecha = $this->factura['Fecha_Documento'];
        if (defined('FE_USAR_FECHA_ACTUAL') && FE_USAR_FECHA_ACTUAL) {
            $fecha = date("Y-m-d H:i:s");
        }
        $neto = number_format($this->totales['Total'] + $this->totales['Total_Iva'], 2, ".", "");
        $variable = $this->factura['Codigo'] . "" . str_replace(" ", "", $fecha) . "-05:00" . number_format($this->totales['Total'], 2, ".", "") . "01" . number_format($this->totales['Total_Iva'], 2, ".", "") . "040.00030.00" . $neto . $nit . $this->cliente['Id_Cliente'] . $this->resolucion['Clave_Tecnica'] . '1';
        return hash('sha384', $variable);
    }

    private function getDatos($tipo_factura, $id_factura, $resolucion_facturacion)
    {

        $oItem = new complex("Resolucion", "Id_Resolucion", $resolucion_facturacion);
        $this->resolucion = $oItem->getData();
        unset($oItem);

        $oItem = new complex($tipo_factura, "Id_" . $tipo_factura, $id_factura);
        $this->factura = $oItem->getData();
        unset($oItem);

        $query = "SELECT C.*,(SELECT D.Nombre FROM Departamento D WHERE D.Id_Departamento=C.Id_Departamento) as Departamento, (SELECT M.Nombre FROM Municipio M WHERE M.Id_Municipio=C.Id_Municipio) as Ciudad FROM Configuracion C WHERE C.Id_Configuracion=1";

        $oCon = new consulta();
        $oCon->setQuery($query);
        $this->configuracion = $oCon->getData();
        unset($oItem);

        /*  if($tipo_factura=="Factura_Administrativa"){
                    if($this->factura['Tipo_Cliente']=='Funcionario'){
                        $tipo_id = 'C.Identificacion_Funcionario';
                    }else{
                        $tipo_id = "C.Id_".$this->factura['Tipo_Cliente'];
                    }
                       $query="SELECT C.*,(SELECT D.Nombre FROM  Departamento D WHERE D.Id_Departamento=C.Id_Departamento) as Departamento,
                                (SELECT M.Nombre FROM Municipio M WHERE M.Id_Municipio=C.Id_Municipio) as Ciudad
                                FROM ".$this->factura['Tipo_Cliente']." C WHERE " .$tipo_id. " = ".$this->factura['Id_Cliente'];
           
                }else{
                 
                    $query="SELECT C.*,(SELECT D.Nombre FROM Departamento D WHERE D.Id_Departamento=C.Id_Departamento) as Departamento, (SELECT M.Nombre FROM Municipio M WHERE M.Id_Municipio=C.Id_Municipio) as Ciudad FROM Cliente C WHERE C.Id_Cliente=".$this->factura['Id_Cliente'];
           
                }
                $oCon=new consulta();
                $oCon->setQuery($query);
                $this->cliente=$oCon->getData();
                unset($oCon); */

        #CARLOS CARDONA ---------------

        if ($tipo_factura == "Factura_Administrativa") {

            $this->cliente = $this->getTercero();
        } else {

            $this->cliente = $this->getCliente();
        }




        if ($tipo_factura != "Factura_Capita" && $tipo_factura != "Factura_Administrativa") {
            $query = 'SELECT PF.*, P.Codigo_Cum as CUM, IFNULL(CONCAT(P.Nombre_Comercial, " - ",P.Principio_Activo, " ", P.Cantidad,"", P.Unidad_Medida, " " , P.Presentacion," (LAB- ", P.Laboratorio_Comercial,") ", P.Invima, " CUM:", P.Codigo_Cum, " - Lote: ",PF.Lote), CONCAT(P.Nombre_Comercial, " (LAB-", P.Laboratorio_Comercial, ") - Lote: ",PF.Lote)) as Producto 
                    FROM Producto_' . $tipo_factura . ' PF 
                    INNER JOIN Producto P ON PF.Id_Producto=P.Id_Producto 
                    WHERE Id_' . $tipo_factura . '=' . $id_factura;

            $oCon = new consulta();
            $oCon->setTipo('Multiple');
            $oCon->setQuery($query);
            $this->productos = $oCon->getData();
            unset($oCon);


            $tip = '';
            if ($tipo_factura == "Factura_Venta") {
                $tip = '_Venta';
            }
            $query = 'SELECT IFNULL(SUM(Cantidad*Precio' . $tip . '),0) as Total, IFNULL(SUM((Cantidad*Precio' . $tip . ')*(Impuesto/100)),0) as Total_Iva, IFNULL(SUM(ROUND(Cantidad*Descuento)),0) as Descuento, Impuesto FROM Producto_' . $tipo_factura . ' WHERE Id_' . $tipo_factura . '=' . $id_factura;
            $oCon = new consulta();
            $oCon->setQuery($query);
            $this->totales = $oCon->getData();
            unset($oCon);
        } elseif ($tipo_factura == "Factura_Capita") {

            $query = 'SELECT PF.*, IFNULL(F.Mes,"") as CUM, PF.Descripcion as Producto 
                    FROM Descripcion_' . $tipo_factura . ' PF 
                    INNER JOIN ' . $tipo_factura . ' F ON F.Id_' . $tipo_factura . ' = PF.Id_' . $tipo_factura . '
                    WHERE PF.Id_' . $tipo_factura . '=' . $id_factura;

            $oCon = new consulta();
            $oCon->setTipo('Multiple');
            $oCon->setQuery($query);
            $this->productos = $oCon->getData();
            unset($oCon);


            $tip = '';

            $query = 'SELECT IFNULL(SUM(Cantidad*Precio),0) as Total, IFNULL(SUM((Cantidad*Precio)*(Impuesto/100)),0) as Total_Iva, IFNULL(SUM(ROUND(Cantidad*Descuento)),0) as Descuento, Impuesto 
                    FROM Descripcion_' . $tipo_factura . '
                    WHERE Id_' . $tipo_factura . '=' . $id_factura;
            $oCon = new consulta();
            $oCon->setQuery($query);
            $this->totales = $oCon->getData();
            unset($oCon);
        } elseif ($tipo_factura == "Factura_Administrativa") {

            $query = 'SELECT PF.*, PF.Referencia as CUM, PF.Descripcion as Producto 
                    FROM Descripcion_' . $tipo_factura . ' PF 
                    INNER JOIN ' . $tipo_factura . ' F ON F.Id_' . $tipo_factura . ' = PF.Id_' . $tipo_factura . '
                    WHERE PF.Id_' . $tipo_factura . '=' . $id_factura;

            $oCon = new consulta();
            $oCon->setTipo('Multiple');
            $oCon->setQuery($query);
            $this->productos = $oCon->getData();
            unset($oCon);

            $tip = '';

            $query = 'SELECT IFNULL(SUM(Cantidad*Precio),0) as Total, IFNULL(SUM((Cantidad*Precio)*(Impuesto/100)),0) as Total_Iva, IFNULL(SUM(ROUND(Cantidad*Descuento)),0) as Descuento, Impuesto 
                    FROM Descripcion_' . $tipo_factura . '
                    WHERE Id_' . $tipo_factura . '=' . $id_factura;
            $oCon = new consulta();
            $oCon->setQuery($query);
            $this->totales = $oCon->getData();
            unset($oCon);
        }
    }

    private function getNombre()
    {
        $nit = self::getNit();
        $codigo = (int)str_replace($this->resolucion['Codigo'], "", $this->factura['Codigo']);
        $nombre = str_pad($nit, 10, "0", STR_PAD_LEFT) . "000" . date("y") . str_pad($codigo, 8, "0", STR_PAD_LEFT);
        return $nombre;
    }

    function getNit()
    {
        $nit = explode("-", $this->configuracion['NIT']);
        $nit = str_replace(".", "", $nit[0]);
        return $nit;
    }

    function getFecha($tipo)
    {
        $fecha = explode(" ", $this->factura['Fecha_Documento']);

        if ($tipo == 'Fecha') {
            return $fecha[0];
        } elseif ($tipo == 'Hora') {
            return $fecha[1];
        }
    }

    private function getImpuesto()
    {
        $query = 'SELECT * FROM Impuesto WHERE Valor>0 LIMIT 1';
        $oCon = new Consulta();
        $oCon->setQuery($query);
        $iva = $oCon->getData();

        return $iva['Valor'];
    }

    private function GetQr($cufe)
    {
        $fecha = $this->factura['Fecha_Documento'];
        if (defined('FE_USAR_FECHA_ACTUAL') && FE_USAR_FECHA_ACTUAL) {
            $fecha = date("Y-m-d H:i:s");
        }
        $fecha = str_replace(":", "", $fecha);
        $fecha = str_replace("-", "", $fecha);
        $fecha = str_replace(" ", "", $fecha);

        $qr = "NumFac: " . $this->factura['Codigo'] . "\n";
        $qr .= "FecFac: " . $fecha . "\n";
        $qr .= "NitFac: " . $this->getNit() . "\n";
        $qr .= "DocAdq: " . $this->factura['Id_Cliente'] . "\n";
        $qr .= "ValFac: " . number_format($this->totales['Total'], 2, ".", "") . "\n";
        $qr .= "ValIva: " . number_format($this->totales['Total_Iva'], 2, ".", "") . "\n";
        $qr .= "ValOtroIm: 0.00 \n";
        $qr .= "ValFacIm: " . number_format(($this->totales['Total_Iva'] + $this->totales['Total']), 2, ".", "") . "\n";
        $qr .= "CUFE: " . $cufe . "\n";
        $qr = generarqrFE($qr);

        return ($qr);
    }

    private function GetTercero()
    {
        $cliente = [];
        $query = 'SELECT * FROM Factura_Administrativa WHERE Id_Factura_Administrativa = ' . $this->factura['Id_Factura_Administrativa'];
        $oCon = new consulta();
        $oCon->setQuery($query);

        $facturaAdmin = $oCon->getData();
        unset($oCon);


        $query = '';
        switch ($facturaAdmin['Tipo_Cliente']) {
            case 'Funcionario':
                $query = 'SELECT "Funcionario" AS Tipo_Tercero, Identificacion_Funcionario AS Id_Cliente , "No" as Contribuyente, "No" as Autorretenedor,
                                        CONCAT_WS(" ",Nombres,Apellidos)AS Nombre,
                                        Correo AS Correo_Persona_Contacto , Celular, "Natural" AS Tipo, "CC" AS Tipo_Identificacion,
                        "" AS Digito_Verificacion, "Simplificado" AS Regimen, Direccion_Residencia AS Direccion, Telefono,
                        IFNULL(Id_Municipio,99) AS Id_Municipio , 1 AS Condicion_Pago
                            FROM Funcionario WHERE Identificacion_Funcionario = ' . $facturaAdmin['Id_Cliente'];
                break;

            case 'Proveedor':
                $query = 'SELECT "Proveedor" AS Tipo_Tercero, Id_Proveedor AS Id_Cliente , "No" as Contribuyente, "No" as Autorretenedor,
                                    
                                    (CASE 
                                        WHEN Tipo = "Juridico" THEN Razon_Social
                                        ELSE  COALESCE(Nombre, CONCAT_WS(" ",Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido) )
                                        
                                    END) AS Nombre,
                                    Correo AS Correo_Persona_Contacto,
                                        Celular, Tipo, "NIT" AS Tipo_Identificacion,
                                    Digito_Verificacion, Regimen, Direccion ,Telefono,
                    Id_Municipio, IFNULL(Condicion_Pago , 1 ) as Condicion_Pago
                        FROM Proveedor WHERE Id_Proveedor = ' . $facturaAdmin['Id_Cliente'];
                break;

            case 'Cliente':
                return $this->getCliente();
                break;

            default:

                break;
        }

        $oCon = new consulta();
        $oCon->setQuery($query);

        $cliente = $oCon->getData();
        unset($oCon);

        return $cliente;
    }

    private function getCliente()
    {
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
                 FROM Cliente WHERE Id_Cliente =' . $this->factura['Id_Cliente'];
        $oCon = new consulta();
        $oCon->setQuery($query);
        $cliente = $oCon->getData();

        unset($oCon);
        return $cliente;
    }


    private function getDataDis()
    {
        // Solo procesar datos del sector salud si es factura de dispensación
        if ($this->tipo_factura != 'Factura') {
            return "";
        }

        $modulo = '';
        if (is_array($this->resolucion)) {
            if (isset($this->resolucion['Modulo'])) {
                $modulo = $this->resolucion['Modulo'];
            } elseif (isset($this->resolucion['modulo'])) {
                $modulo = $this->resolucion['modulo'];
            }
        }
        $moduloNormalizado = strtolower(trim((string) $modulo));
        if ($moduloNormalizado !== '' && strpos($moduloNormalizado, 'farmacia') !== false) {
            return "";
        }
        if ($moduloNormalizado !== strtolower('NoPos')) {
            return "";
        }

        // Verificar que la factura tenga dispensación asociada
        if (empty($this->factura['Id_Dispensacion'])) {
            return "";
        }

        try {
            // Obtener datos del sector salud
            $healt_sector_data = getDatosDisHelper(
                $this->factura['Id_Dispensacion'],
                $this->configuracion,
                $this->cliente
            );

            // Validar campos mínimos obligatorios (12 campos según XML real)
            $campos_requeridos = [
                'Codigo_Prestador',
                'Tipo_Documento_Identificacion',
                'Numero_Documento_Identificacion',
                'Primer_Nombre',
                'Primer_Apellido',
                'Modalidad_Contratacion',
                'Cobertura_Plan_Beneficios',
                'Numero_Contrato',
                'Copago',
                'Cuota_Moderadora',
                'Cuota_Recuperacion',
                'Pagos_Compartidos'
            ];

            $campos_faltantes = [];
            foreach ($campos_requeridos as $campo) {
                if (!isset($healt_sector_data[$campo]) || $healt_sector_data[$campo] === '') {
                    $campos_faltantes[] = $campo;
                }
            }

            if (!empty($campos_faltantes)) {
                error_log("ADVERTENCIA: Campos vacíos en Sector Salud - Factura {$this->factura['Codigo']}: " . implode(', ', $campos_faltantes));
            }

            return $healt_sector_data;
        } catch (Exception $e) {
            error_log("ERROR al obtener datos del Sector Salud - Factura {$this->factura['Codigo']}: " . $e->getMessage());
            return "";
        }
    }
}

