<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-type:application/json');

include_once '../../class/class.complex.php';
include_once '../../class/class.consulta.php';
include_once '../../class/class.contabilizar.php';
require_once '../../class/class.validacion_cufe.php';
require_once '../../class/class.php_mailer.php';

require_once '../../class/class.qr.php';

$dian_response = (isset($_REQUEST['datos']) ? $_REQUEST['datos'] : '');
$evento = (isset($_REQUEST['evento']) ? $_REQUEST['evento'] : '');
$idevento = (isset($_REQUEST['id']) ? $_REQUEST['id'] : '');

$queryConfig = "SELECT C.*,(SELECT D.Nombre FROM Departamento D WHERE D.Id_Departamento=C.Id_Departamento) as Departamento, (SELECT M.Nombre FROM Municipio M WHERE M.Id_Municipio=C.Id_Municipio) as Ciudad FROM Configuracion C WHERE C.Id_Configuracion=1";

$oCon = new consulta();
$oCon->setQuery($queryConfig);
$configuracion = $oCon->getData();
unset($oCon);

$dian_response = json_decode($dian_response, true);


    
try {
    $respuesta_dian = isset($dian_response['Json']) ? $dian_response['Json'] : $dian_response;
    $respuesta = GenerarFactura($respuesta_dian, $evento);
    echo json_encode($respuesta);
} catch (\Throwable $th) {
    http_response_code(403);
    echo json_encode($th->getMessage());
}

function GenerarFactura($respuesta_dian, $evento)
{
    global $idevento;
    
    
    $cuds = $respuesta_dian['cune'];

    $error = $respuesta_dian["ResponseDian"]["Envelope"]["Body"]["SendEventUpdateStatusResponse"]["SendEventUpdateStatusResult"]['ErrorMessage'];
    $documentKey = $respuesta_dian["ResponseDian"]["Envelope"]["Body"]["SendEventUpdateStatusResponse"]["SendEventUpdateStatusResult"]['XmlDocumentKey'];
    $XmlFileName =$respuesta_dian["ResponseDian"]["Envelope"]["Body"]["SendEventUpdateStatusResponse"]["SendEventUpdateStatusResult"]['XmlFileName'];
    $errores_dian = isset($error) ? $error : [];

    if (count($errores_dian) > 0) {
        $errores_dian = implode(" - ", $errores_dian);
    } else {
        $errores_dian = "";
    }
    $aplication_response = $respuesta_dian["ResponseDian"]["Envelope"]["Body"]["SendEventUpdateStatusResponse"]["SendEventUpdateStatusResult"]["XmlBase64Bytes"];
    $aplication_response = base64_decode($aplication_response);
    if (strpos($errores_dian, "procesado anteriormente") !== false) {
        $cuds = $documentKey;
    }

    $documento = true;
    //$documento = validarCuds($cuds);
    
   
    if ($documento || $idevento) {
        $estado = "true";
    
        $respuesta_dian['Respuesta'] = $documento['StatusMessage'];
        if ($estado == "true") {
            $oItem = new complex($evento, "Codigo", $XmlFileName, 'str');
            $oItem->Cude = $cuds;
            $oItem->Procesada = $estado;
            $oItem->save();
            $id = $oItem->getData()['Id_' . $evento];
            unset($oItem);
    
            $oItem = new complex($evento, "Id_$evento", $id);
            $ev = $oItem->getData();
            unset($oItem);
            

        if ($evento == 'Aceptacion_Tacita') {
            $oItem = new complex('Factura_Venta', 'Id_Factura_Venta', $ev['Id_Factura']);
            $oItem->Estado_Aceptacion = '034';
            $oItem->Fecha_Estado = date('Y-m-d');
            $oItem->save();
            unset($oItem);
        }
        
        if ($evento == 'Acuse_Recibo_Factura' || $evento == 'Acuse_Recibo_Bien_Servicio' || $evento == 'Aceptacion_Expresa_Factura' || $evento == 'Rechazo_Factura') {
            $oItem = new complex("Resolucion", "Id_Resolucion", $ev['Id_Resolucion']);
            $resolucion = $oItem->getData();
            unset($oItem);

            $query = "SELECT 'Proveedor' AS Tipo_Tercero, P.Id_Proveedor AS Id_Proveedor , 'No' as Contribuyente, 'No' as Autorretenedor,
                        (CASE
                            WHEN P.Tipo = 'Juridico' THEN Razon_Social
                            ELSE COALESCE(P.Nombre, CONCAT_WS(' ', P.Primer_Nombre, P.Segundo_Nombre, P.Primer_Apellido, P.Segundo_Apellido))
                        END) AS Nombre,
                        P.Correo AS Correo_Persona_Contacto,
                        P.Celular, Tipo, 'NIT' AS Tipo_Identificacion,
                        P.Digito_Verificacion, P.Regimen, P.Direccion, P.Telefono,
                        IFNULL(Condicion_Pago, 1) as Condicion_Pago
                    FROM Proveedor P
                    INNER JOIN Factura_Recibida F ON F.Id_Proveedor = P.Id_Proveedor
                    WHERE F.Id_Factura_Recibida = $ev[Id_Factura_Recibida]";

            $oCon = new consulta();
            $oCon->setQuery($query);
            $proveedor = $oCon->getData();

            $respuesta['Correo'] = EnviarCorreoEvento(
                $documento,
                $cuds,
                $ev,
                $resolucion,
                $proveedor,
                $respuesta_dian['Respuesta'],
                $evento
            );
        }

        $respuesta['tipo'] = "success";
        $respuesta["Procesada"] = $documento['Codigo'] . " transmitido correctamente";
        return $respuesta;
        }
    }

        $respuesta['tipo'] = "error";
        $respuesta["Procesada"] = "No Procesada, cude no existe $cuds";
        return $respuesta;
    }

function EnviarCorreoEvento($aplication_response, $cude, $ev, $resolucion, $proveedor, $dian, $evento)
{
    global $configuracion;
    switch ($evento) {
        case 'Acuse_Recibo_Factura':
            $ev['Codigo_Tipo'] = '030';
            $ev['Nombre_Evento'] = 'Acuse de recibo de Factura Electrónica de Venta';
            break;
        case 'Acuse_Recibo_Bien_Servicio':
            $ev['Nombre_Evento'] = 'Recibo del bien y/o prestación del servicio';
            $ev['Codigo_Tipo'] = '032';
            break;
        case 'Aceptacion_Expresa_Factura':
            $ev['Nombre_Evento'] = 'Aceptacion Expresa';
            $ev['Codigo_Tipo'] = '033';
            break;
        case 'Rechazo_Factura':
            $ev['Nombre_Evento'] = 'Reclamo de la Factura Electrónica de Venta';
            $ev['Codigo_Tipo'] = '031';
            break;
        default:
            return 'Correo no enviado';
            break;
    }
    $oItem = new complex('Factura_Recibida', 'Id_Factura_Recibida', $ev['Id_Factura_Recibida']);
    $fact = $oItem->getData();

    $contenido = GetHtmlFactura($dian, $ev, $proveedor, $configuracion, $fact);
    $xml = getXml($aplication_response, $cude, $ev, $resolucion, $proveedor);
    // if($evento=='Rechazo_Factura'){

    //     echo $contenido; exit;
    // }
    $destino = ((($proveedor["Correo_Persona_Contacto"] != "" && $proveedor["Correo_Persona_Contacto"] != "NULL") ? $proveedor["Correo_Persona_Contacto"] : 'facturacionelectronicacont@prohsa.com'));

    $nit = getNit();
    $asunto = "Evento;$fact[Codigo_Factura];$nit;PRODUCTOS HOSPITALARIOS S.A.;$ev[Codigo];$ev[Codigo_Tipo];PRO-H";

    $zip = new ZipArchive();
    $filename = 'https://api-dian.sigesproph.com.co/api-dian/storage/app/zip/1/' . $resolucion["resolution_id"] . '/' . $ev['Codigo'] . '.zip';

    if ($zip->open($filename, ZIPARCHIVE::CREATE) === true) {
        $zip->addFile($xml, "ad_" . $ev['Codigo'] . ".xml");
    }
    $zip->close();
    $email = new EnviarCorreo();
    $respuesta = $email->EnviarFacturaDian($destino, $asunto, $contenido, $filename, "");
    unlink($filename);
    return ($respuesta);
}

function getNit()
{
    global $configuracion;
    $nit = explode("-", $configuracion['NIT']);
    $nit = str_replace(".", "", $nit[0]);
    return $nit;
}

function GetHtmlFactura($dian, $ev, $proveedor, $configuracion, $fact)
{
    // global ;
    $html = '<!doctype html>
            <html>
            <head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">
                <meta name="viewport" content="width=device-width" />

                <title> - Productos Hospitalarios (Pro H) S.A</title>
                <style>
                    img{border:none;-ms-interpolation-mode:bicubic;max-width:100%}body{background-color:#f6f6f6;font-family:sans-serif;-webkit-font-smoothing:antialiased;font-size:14px;line-height:1.4;margin:0;padding:0;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%}.body{background-color:#f6f6f6;width:100%}.container{display:block;margin:0 auto!important;max-width:580px;padding:10px;width:580px}.content{box-sizing:border-box;display:block;margin:0 auto;max-width:580px;padding:10px}.main{background:#fff;border-radius:3px;width:100%}.wrapper{box-sizing:border-box;padding:20px}.content-block{padding-bottom:10px;padding-top:10px}.footer{clear:both;margin-top:10px;text-align:center;width:100%}.footer a,.footer p,.footer span,.footer td{color:#999;font-size:12px;text-align:center}h5{font-size:14px;font-weight:700;text-align:left;color:#3c5dc6}p{font-family:sans-serif;font-size:11px;font-weight:400;margin:0;margin-bottom:15px;text-align:justify}span{color:#000;font-family:sans-serif;font-weight:600}a{color:#3c5dc6;text-decoration:none}.logo{border:0;outline:0;text-decoration:none;display:block;text-align:center}.align-center{text-align:center!important}.preheader{color:transparent;display:none;height:0;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;visibility:hidden;width:0}.powered-by a{text-decoration:none;text-align:center!important}hr{border:0;border-bottom:1px solid #eeeef0;margin:8px 0}@media all{.ExternalClass{width:100%}.ExternalClass,.ExternalClass div,.ExternalClass font,.ExternalClass p,.ExternalClass span,.ExternalClass td{line-height:100%}.apple-link a{color:inherit!important;font-family:inherit!important;font-size:inherit!important;font-weight:inherit!important;line-height:inherit!important;text-decoration:none!important}#MessageViewBody a{color:inherit;text-decoration:none;font-size:inherit;font-family:inherit;font-weight:inherit;line-height:inherit}}
                </style>
            </head>

            <body class="">
                <span class="preheader">Notificación de Evento:  ' . $ev["Nombre_Evento"] . '</span>
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
                                                        <p>Estimado, <span>' . $proveedor["Nombre"] . '</span></p>
                                                        <p>Ha recibido un documento electrónico generedo y enviado mediante el sistema de Facturación Electrónica de Productos Hospitalarios S.A. con la siguiente información:</p>
                                                        <hr>
                                                        <h5>Datos del Emisor</h5>
                                                        <hr>
                                                        <p><span>Nombre: </span>' . $configuracion["Nombre_Empresa"] . '</p>
                                                        <p><span>Identificación: </span>' . $configuracion["NIT"] . '</p>
                                                        <hr>
                                                        <h5>Información del Documento</h5>
                                                        <hr>
                                                        <p><span>Tipo: </span>' . "$ev[Nombre_Evento] ($ev[Codigo_Tipo])  " . '</p>
                                                        <p><span>Numero: </span>' . $ev["Codigo"] . '</p>
                                                        <hr>
                                                        <h5>Documento de referencia</h5>
                                                        <hr>
                                                        <p><span>Tipo: </span> Factura Electronica de Venta </p>
                                                        <p><span>Numero: </span>' . $fact["Codigo_Factura"] . '</p>
                                                        <p><span>Fecha: </span>' . $fact["Fecha_Factura"] . '</p>
                                                        <hr>
                                                        <p>Adjunto encontrará el documento electrónico en formato XML.</p>
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


function validarCuds($cufe)
{
    
    $login = 'facturacion@prohsa.com';
    $password = '804016084';
    
    $cufe = strtolower($cufe);
    
    $url = "https://api-dian.sigesproph.com.co/api/ubl2.1/status/document/$cufe";

    $ch = curl_init($url);
    
    curl_setopt($ch, CURLOPT_SSLVERSION, 4);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);  
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    
    $headers = array(
        "Content-type: application/json",
        "Accept: application/json",
        "Cache-Control: no-cache",
        "Authorization: Basic " . base64_encode($login . ':' . $password),
        "Pragma: no-cache",
        "SOAPAction:\"" . $url . "\"",
        "Content-length: 0" ,
    );

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
    $result = curl_exec($ch);
    
   // var_dump($result);

    $data = json_decode($result, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "Error al decodificar la respuesta JSON.";
        exit;
    }

    // Para verificar la estructura de datos después de decodificar JSON
    //var_dump('Respuesta decodificada en JSON:', $data);

    if (isset($data['ResponseDian']['Envelope']['Body']['GetStatusResponse']['GetStatusResult']['XmlBase64Bytes'])) {
        $xml_base64 = $data['ResponseDian']['Envelope']['Body']['GetStatusResponse']['GetStatusResult']['XmlBase64Bytes'];
        $xml_content = base64_decode($xml_base64);

        if ($xml_content === false) {
            echo "Error al decodificar los datos base64.";
            exit;
        }

        // Para verificar el contenido XML después de decodificar Base64
        //var_dump('Contenido XML decodificado:', $xml_content);

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        if (!$dom->loadXML($xml_content)) {
            echo "Error al cargar XML.";
            exit;
        }

        $respuesta = [];

        // Verificar que los datos específicos se están extrayendo correctamente del XML
        $uuid = $dom->getElementsByTagName('UUID')->item(0)->nodeValue ?? null;
        $codigo = $dom->getElementsByTagName('ID')->item(0)->nodeValue ?? null;
        $response_code = $dom->getElementsByTagName('ResponseCode')->item(0)->nodeValue ?? null;
        $description = $dom->getElementsByTagName('Description')->item(0)->nodeValue ?? null;

        // Para confirmar la extracción de cada valor desde el XML
        //var_dump('UUID:', $uuid);
        //var_dump('Codigo (ID):', $codigo);
        //var_dump('Response Code:', $response_code);
        //var_dump('Status Message (Description):', $description);

        // Asignación final a la respuesta
        $respuesta['Codigo'] = $codigo;
        $respuesta['CUDE'] = $uuid;
        $respuesta['ResponseCode'] = $response_code;
        $respuesta['StatusMessage'] = $description;

        // Para verificar la estructura final del array de respuesta antes de retornarlo
        //var_dump('Respuesta final:', $respuesta);

        return $respuesta;
    } else {
        echo "No se encontró el campo `XmlBase64Bytes` en la respuesta.";
        exit;
    }
}



function ObtenerCudsRespuesta($aplication_response)
{
    preg_match('/<cac:DocumentReference>(.*?)<\/cac:DocumentReference>/is', $aplication_response, $doc);
    preg_match('/<cbc:UUID(.*?)\/cbc:UUID/is', $doc[1], $uuid);
    preg_match('/>(.*?)</is', $uuid[1], $uui);
    $respuesta = $uui[1];
    return $respuesta;
}

function getXml($aplication_response, $cude, $ev, $resolucion, $proveedor)
{

    global $configuracion;

    preg_match('/IssueDate>(.*?)<\/cbc:IssueDate/is', $aplication_response, $coincidencias);
    $fecha = $coincidencias[1];
    preg_match('/IssueTime>(.*?)<\/cbc:IssueTime/is', $aplication_response, $coincidencias2);
    $hora = $coincidencias2[1];
    preg_match('/ResponseCode>(.*?)<\/cbc:ResponseCode/is', $aplication_response, $coincidencias3);
    $respuesta = $coincidencias3[1];

    $name_file = "$resolucion[Codigo]$ev[Codigo]";
    $xml_invoice = 'https://api-dian.sigesproph.com.co/api-dian/storage/app/xml/1/' . $resolucion["resolution_id"] . '/' . $name_file . '.xml';
    if (!file_exists($xml_invoice)) {
        $xml_invoice = 'https://api-dian.sigesproph.com.co/api-dian/storage/app/xml/1/AE/AE' . $ev["Codigo"] . '.xml';
    } 
    $xml_factura = file_get_contents($xml_invoice);

    $num = explode("-", $configuracion["NIT"]);
    $nit = str_replace(".", "", $num[0]);
    $dv = $num[1];

    $xml = '<?xml version="1.0" encoding="utf-8" standalone="no"?>
    <AttachedDocument xmlns="urn:oasis:names:specification:ubl:schema:xsd:AttachedDocument-2" 
    xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2"
    xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" 
    xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2">
    <cbc:UBLVersionID>UBL 2.1</cbc:UBLVersionID>
    <cbc:CustomizationID>Documentos adjuntos</cbc:CustomizationID>
    <cbc:ProfileID>Factura ELectrónica de Venta</cbc:ProfileID>
    <cbc:ProfileExecutionID>1</cbc:ProfileExecutionID>
    <cbc:ID>' . $ev["Codigo"] . '</cbc:ID>
    <cbc:IssueDate>' . $fecha . '</cbc:IssueDate>
    <cbc:IssueTime>' . $hora . '</cbc:IssueTime>
    <cbc:DocumentType>Contenedor de Factura Electrónica</cbc:DocumentType>
    <cbc:ParentDocumentID>' . $ev["Codigo"] . '</cbc:ParentDocumentID>
    <cac:SenderParty>
    <cac:PartyTaxScheme>
    <cbc:RegistrationName>' . $configuracion["Nombre_Empresa"] . '</cbc:RegistrationName>
    <cbc:CompanyID schemeName="31" schemeID="' . $dv . '" schemeAgencyName="CO, DIAN (Dirección de Impuestos y Aduanas Nacionales)" schemeAgencyID="195">' . $nit . '</cbc:CompanyID>
    <cbc:TaxLevelCode listName="48">R-99-PN</cbc:TaxLevelCode>
    <cac:TaxScheme>
    <cbc:ID>01</cbc:ID>
    <cbc:Name>IVA</cbc:Name>
    </cac:TaxScheme>
    </cac:PartyTaxScheme>
    </cac:SenderParty>
    <cac:ReceiverParty>
    <cac:PartyTaxScheme>
    <cbc:RegistrationName>' . $proveedor["Nombre"] . '</cbc:RegistrationName>
    <cbc:CompanyID schemeName="31" schemeID="' . $proveedor["Digito_Verificacion"] . '" schemeAgencyName="CO, DIAN (Dirección de Impuestos y Aduanas Nacionales)" schemeAgencyID="195">' . $proveedor["Id_Proveedor"] . '</cbc:CompanyID>
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
    <cbc:ID>' . $ev["Codigo"] . '</cbc:ID>
    <cbc:UUID schemeName="CUFE-SHA384">' . $cude . '</cbc:UUID>
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
    
    $xml_resp = 'https://api-dian.sigesproph.com.co/api-dian/storage/app/ad/1/' . $resolucion["resolution_id"] . '/ad' . $name_file . '.xml';

    file_put_contents($xml_resp, $xml);


    return ($xml_resp);
}
