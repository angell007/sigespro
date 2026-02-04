<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

include_once('../../class/class.lista.php');
include_once('../../class/class.complex.php');
include_once('../../class/class.consulta.php');
require_once('../../class/class.php_mailer.php');
require_once('../../class/class.qr.php');

$nota_credito = '';
$configuracion = '';
$productos = [];
$cliente = '';
$totales = '';
$tipo_nota_credito = '';
$id_nota_credito = '';
$factura = '';

$respuesta_dian = (isset($_REQUEST['datos']) ? $_REQUEST['datos'] : '');
$tipo = (isset($_REQUEST['tipo']) ? $_REQUEST['tipo'] : 'Nota_Credito');
$id = (isset($_REQUEST['id_nota']) ? $_REQUEST['id_nota'] : '');
$resolucion = (isset($_REQUEST['resolucion']) ? $_REQUEST['resolucion'] : '24');
$respuesta_dian = json_decode($respuesta_dian, true);

init($tipo, $id, $resolucion);

$respuesta = actualizarCampos($respuesta_dian);
echo json_encode($respuesta);
exit;
function init($tipo_nota_credito2, $id_nota_credito2, $resolucion_facturacion)
{
    global $tipo_nota_credito, $id_nota_credito;
    $tipo_nota_credito = $tipo_nota_credito2;
    $id_nota_credito = $id_nota_credito2;
    getDatos($tipo_nota_credito, $id_nota_credito, $resolucion_facturacion);
}


function actualizarCampos($respuesta_dian)
{

    global $tipo_nota_credito, $id_nota_credito;
    //  $respuesta_dian =$GetApi($datos);

    $cude = $respuesta_dian["cude"];

    $qr = GetQr($cude);
    $r = $respuesta_dian["ResponseDian"]["Envelope"]["Body"]["SendBillSyncResponse"]["SendBillSyncResult"]["IsValid"];
    $aplication_response = $respuesta_dian["ResponseDian"]["Envelope"]["Body"]["SendBillSyncResponse"]["SendBillSyncResult"]["XmlBase64Bytes"];
    $aplication_response = base64_decode($aplication_response);

    $error = $respuesta_dian["ResponseDian"]["Envelope"]["Body"]["SendBillSyncResponse"]["SendBillSyncResult"]['ErrorMessage'];
    $errores_dian = isset($error) ? $error : [];

    $estado = $r;
    if (count($errores_dian) > 0) {
        $errores_dian = implode(" - ", $errores_dian);
    } else {
        $errores_dian = "";}
    if (strpos($errores_dian, "procesado anteriormente") !== false) {
        $estado = "true";
    } else {
        $estado = $respuesta_dian["ResponseDian"]["Envelope"]["Body"]["SendBillSyncResponse"]["SendBillSyncResult"]["IsValid"];
    }
    $respuesta_dian['Respuesta'] = $respuesta_dian["ResponseDian"]["Envelope"]["Body"]["SendBillSyncResponse"]["SendBillSyncResult"]["StatusMessage"];
  
    if ($estado == "true") {
        $respuesta['Actualizado'] = $estado;
        $oItem = new complex($tipo_nota_credito, "Id_" . $tipo_nota_credito, $id_nota_credito);
        $oItem->Cude = $cude;
        $oItem->Codigo_Qr = $qr;
        $oItem->Procesada = $estado;
        $oItem->save();
        unset($oItem);
        $respuesta['Actualizado'] = $estado;
        $respuesta["Respuesta_Correo"] =EnviarMail($cude,$qr,$respuesta_dian["Respuesta"], $aplication_response); 
        $respuesta["Detalles"] = $respuesta_dian["message"];
        $data["Cude"] = $cude;
        $data["Qr"] = $qr;
        $respuesta["Datos"] = $data;
        http_response_code(200);
        return ($respuesta);
    }
    http_response_code(400);
}
function GetQr($cude)
{
    global $nota_credito, $totales;
    $fecha = str_replace(":", "", $nota_credito['Fecha']);
    $fecha = str_replace("-", "", $fecha);
    $fecha = str_replace(" ", "", $fecha);

    $qr = "NotaCredito: " . $nota_credito['Codigo'] . "\n";
    $qr .= "Fecha: " . $fecha . "\n";
    $qr .= "NitFac: " . getNit() . "\n";
    $qr .= "DocAdq: " . $nota_credito['Id_Cliente'] . "\n";
    $qr .= "ValFac: " . number_format($totales['Total'], 2, ".", "") . "\n";
    $qr .= "ValIva: " . number_format($totales['Total_Iva'], 2, ".", "") . "\n";
    $qr .= "ValOtroIm: 0.00 \n";
    $qr .= "ValFacIm: " . number_format(($totales['Total_Iva'] + $totales['Total']), 2, ".", "") . "\n";
    $qr .= "CUDE: " . $cude . "\n";
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
function getDatos($tipo_nota_credito, $id_nota_credito, $resolucion_facturacion)
{
    global $nota_credito, $totales, $resolucion, $factura, $configuracion, $cliente;
    unset($oItem);

    $oItem = new complex($tipo_nota_credito, "Id_" . $tipo_nota_credito, $id_nota_credito);
    $nota_credito = $oItem->getData();
    unset($oItem);

    $oItem = new complex("Resolucion", "Id_Resolucion", $resolucion_facturacion);
    $resolucion = $oItem->getData();

    $tipoFactura = $tipo_nota_credito == 'Nota_Credito_Global' ? $nota_credito['Tipo_Factura'] : 'Factura_Venta';
    $oItem = new complex($tipoFactura, 'Id_' . $tipoFactura, $nota_credito["Id_Factura"]);
    $factura = $oItem->getData();
    unset($oItem);

    $query = 'SELECT * FROM Configuracion Limit 1';

    $oCon = new consulta();
    $oCon->setQuery($query);
    $configuracion = $oCon->getData();
    unset($oItem);

    $cliente = GetTercero($nota_credito);

    $campoPrecio = $tipo_nota_credito == 'Nota_Credito' ? 'Precio_Venta' : 'Precio_Nota_Credito';

    $query = 'SELECT IFNULL(SUM(Cantidad*' . $campoPrecio . '),0) as Total, IFNULL(SUM((Cantidad*' . $campoPrecio . ')*(Impuesto/100)),0) as Total_Iva,
             /* IFNULL(SUM(ROUND(Cantidad*Descuento)),0) as Descuento, */ Impuesto 
    FROM Producto_' . $tipo_nota_credito . '
    WHERE Id_' . $tipo_nota_credito . '=' . $id_nota_credito;
    $oCon = new consulta();
    $oCon->setQuery($query);
    $totales = $oCon->getData();

    unset($oCon);
}

function EnviarMail($cufe, $qr, $dian, $aplication_response)
{
    global $cliente, $factura, $resolucion, $nota_credito;

    $destino = ((($cliente["Correo"] != "" && $cliente["Correo"] != "NULL") ? $cliente["Correo"] : 'facturacionelectronicacont@prohsa.com'));
    $nit = getNit();
    $asunto = "$nit;PRODUCTOS HOSPITALARIOS S.A.;$nota_credito[Codigo];91;PRO-H";


// $destino = "desarrollo.proh@gmail.com";
    if (strtolower($destino) == 'siifnacion.facturaelectronica@minhacienda.gov.co') {
        $asunto = str_replace('#$', '', $factura['Observaciones']);
        $asunto = "#$" . $asunto . "#$";
    }


    $contenido = GetHtmlFactura($dian);
    $xml = getXml($aplication_response, $cufe);
    $fact = getFact();
    
    // echo $fact; exit;

    $zip = new ZipArchive();
    $filename = '/home/sigesproph/api-dian.sigesproph.com.co/api-dian/storage/app/zip/1/' . $resolucion["resolution_id"] . '/nc' . getNombre() . '.zip';

    if (file_exists($filename)) {
        unlink($filename);
    }

    if ($zip->open($filename, ZIPARCHIVE::CREATE) === true) {
        $zip->addFile($fact, "nc" . getNombre() . '.pdf');
        $zip->addFile($xml, "nc" . getNombre() . '.xml');
    }
    $zip->close();
    $email = new EnviarCorreo();
    if(file_exists($filename) && file_exists($fact) && file_exists($xml)){
         $respuesta = $email->EnviarFacturaDian($destino, $asunto, $contenido, $filename, "");
    }
    unlink($fact);
    unlink($filename);
    return ($respuesta);
}

function getFact()
{
    global $tipo_nota_credito, $resolucion, $id_nota_credito;
    // echo "$tipo_nota_credito, $resolucion, $id_nota_credito"; exit;
    $ruta ='';
    if($tipo_nota_credito == 'Nota_Credito'){
            $ruta = '../notascredito/class_guardar_pdf.php';
    } else if($tipo_nota_credito =='Nota_Credito_Global'){
            $ruta = '../notas_credito_nuevo/class_guardar_pdf.php';
    }

    $ruta_nueva = "/ARCHIVOS/FACTURA_ELECTRONICA_PDF/" . $resolucion["resolution_id"];

    if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $ruta_nueva)) {
        mkdir($_SERVER['DOCUMENT_ROOT'] . $ruta_nueva, 0777, true);
    }

    $nombre_factura = "fv" . getNombre() . ".pdf";
    $ruta_fact = $ruta_nueva . "/" . $nombre_factura;

    try{
        require_once $ruta;
        $pdf = new Nota_Credito_Pdf($id_nota_credito,$ruta_fact);
        $pdf->generarPdf();
    } catch (\Throwable $e) {
        http_response_code(406);
        echo json_encode($e);
    }
    return ($_SERVER['DOCUMENT_ROOT'].$ruta_fact);
    
}

function GetHtmlFactura($dian)
{
    global $factura, $cliente, $configuracion, $totales, $nota_credito;
    $html = '<!doctype html>
            <html>
            <head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">
                <meta name="viewport" content="width=device-width" />

                <title>Nota Crédito Electrónica - Productos Hospitalarios (Pro H) S.A</title>
                <style>
                    img{border:none;-ms-interpolation-mode:bicubic;max-width:100%}body{background-color:#f6f6f6;font-family:sans-serif;-webkit-font-smoothing:antialiased;font-size:14px;line-height:1.4;margin:0;padding:0;-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%}.body{background-color:#f6f6f6;width:100%}.container{display:block;margin:0 auto!important;max-width:580px;padding:10px;width:580px}.content{box-sizing:border-box;display:block;margin:0 auto;max-width:580px;padding:10px}.main{background:#fff;border-radius:3px;width:100%}.wrapper{box-sizing:border-box;padding:20px}.content-block{padding-bottom:10px;padding-top:10px}.footer{clear:both;margin-top:10px;text-align:center;width:100%}.footer a,.footer p,.footer span,.footer td{color:#999;font-size:12px;text-align:center}h5{font-size:14px;font-weight:700;text-align:left;color:#3c5dc6}p{font-family:sans-serif;font-size:11px;font-weight:400;margin:0;margin-bottom:15px;text-align:justify}span{color:#000;font-family:sans-serif;font-weight:600}a{color:#3c5dc6;text-decoration:none}.logo{border:0;outline:0;text-decoration:none;display:block;text-align:center}.align-center{text-align:center!important}.preheader{color:transparent;display:none;height:0;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;visibility:hidden;width:0}.powered-by a{text-decoration:none;text-align:center!important}hr{border:0;border-bottom:1px solid #eeeef0;margin:8px 0}@media all{.ExternalClass{width:100%}.ExternalClass,.ExternalClass div,.ExternalClass font,.ExternalClass p,.ExternalClass span,.ExternalClass td{line-height:100%}.apple-link a{color:inherit!important;font-family:inherit!important;font-size:inherit!important;font-weight:inherit!important;line-height:inherit!important;text-decoration:none!important}#MessageViewBody a{color:inherit;text-decoration:none;font-size:inherit;font-family:inherit;font-weight:inherit;line-height:inherit}}
                </style>
            </head>

            <body class="">
                <span class="preheader">Nota Crédito Electrónica ' . $nota_credito["Codigo"] . '</span>
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
                                                        <p><span>Fecha: </span>' . $nota_credito["Fecha"] . '</p>
                                                        <p><span>Tipo: Nota Credito Electronica </span></p>
                                                        <p><span>Numero: </span>' . $nota_credito["Codigo"] . '</p>
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
// echo $html; exit;
    return ($html);
}
function getXml($aplication_response, $cufe)
{

    global $resolucion, $configuracion, $nota_credito, $cliente;

    preg_match('/IssueDate>(.*?)<\/cbc:IssueDate/is', $aplication_response, $coincidencias);
    $fecha = $coincidencias[1];
    preg_match('/IssueTime>(.*?)<\/cbc:IssueTime/is', $aplication_response, $coincidencias2);
    $hora = $coincidencias2[1];
    preg_match('/ResponseCode>(.*?)<\/cbc:ResponseCode/is', $aplication_response, $coincidencias3);
    $respuesta = $coincidencias3[1];

    $name_file = getNombre();
    $xml_invoice = '/home/sigesproph/api-dian.sigesproph.com.co/api-dian/storage/app/xml/1/' . $resolucion["resolution_id"] . '/nc' . $name_file . '.xml';

    $xml_factura = file_get_contents($xml_invoice);

    // echo $aplication_response;exit;
    $num = explode("-", $configuracion["NIT"]);
    $nit = str_replace(".", "", $num[0]);
    $dv = $num[1];

    $xml = '<?xml version="1.0" encoding="utf-8" standalone="no"?>
    <AttachedDocument xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2" 
    xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" 
    xmlns:ds="http://www.w3.org/2000/09/xmldsig#" 
    xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2" 
    xmlns:sts="dian:gov:co:facturaelectronica:Structures-2-1" 
    xmlns:xades="http://uri.etsi.org/01903/v1.3.2#" 
    xmlns:xades141="http://uri.etsi.org/01903/v1.4.1#" 
    xmlns:ns0="http://www.w3.org/2001/XMLSchema-instance"
    xmlns="urn:oasis:names:specification:ubl:schema:xsd:AttachedDocument-2" 
    ns0:schemaLocation="urn:oasis:names:specification:ubl:schema:xsd:AttachedDocument-2 http://docs.oasis-open.org/ubl/os-UBL-2.1/xsd/maindoc/UBL-AttachedDocument-2.1.xsd">
    <cbc:UBLVersionID>UBL 2.1</cbc:UBLVersionID>
    <cbc:CustomizationID>Documentos adjuntos</cbc:CustomizationID>
    <cbc:ProfileID>Factura Electrónica de Venta</cbc:ProfileID>
    <cbc:ProfileExecutionID>1</cbc:ProfileExecutionID>
    <cbc:ID>' . $nota_credito["Codigo"] . '</cbc:ID>
    <cbc:IssueDate>' . $fecha . '</cbc:IssueDate>
    <cbc:IssueTime>' . $hora . '</cbc:IssueTime>
    <cbc:DocumentType>Contenedor de Factura Electrónica</cbc:DocumentType>
    <cbc:ParentDocumentID>' . $nota_credito["Codigo"] . '</cbc:ParentDocumentID>
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
    <cbc:ID>' . $nota_credito["Codigo"] . '</cbc:ID>
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

    $xml_resp = '/home/sigesproph/api-dian.sigesproph.com.co/api-dian/storage/app/ad/1/' . $resolucion["resolution_id"] . '/ad' . $name_file . '.xml';
    file_put_contents($xml_resp, $xml);

    // echo $xml; exit;
    return ($xml_resp);
}
function GetTercero($nota)
{
    $cliente = [];
    global $tipo_nota_credito;


    if ($tipo_nota_credito == 'Nota_Credito_Global' && $nota['Tipo_Factura'] == 'Factura_Administrativa') {

        $query = 'SELECT * FROM Factura_Administrativa WHERE Id_Factura_Administrativa = ' . $nota['Id_Factura'];
        $oCon = new consulta();
        $oCon->setQuery($query);

        $facturaAdmin = $oCon->getData();
        unset($oCon);


        $query = '';
        switch ($facturaAdmin['Tipo_Cliente']) {
            case 'Funcionario':
                $query = 'SELECT "Funcionario" AS Tipo_Tercero, Identificacion_Funcionario AS Id_Cliente , 
                                CONCAT_WS(" ",Nombres,Apellidos)AS Nombre,
                                Correo,Celular, "Natural" AS Tipo, "CC" AS Tipo_Identificacion,
                                "" AS Digito_Verificacion, "Simplificado" AS Regimen, Direccion_Residencia AS Direccion
                    FROM Funcionario WHERE Identificacion_Funcionario = ' . $facturaAdmin['Id_Cliente'];
                break;

            case 'Proveedor':
                $query = 'SELECT "Proveedor" AS Tipo_Tercero, Id_Proveedor AS Id_Cliente , 
                           
                            (CASE 
                                WHEN Tipo = "Juridico" THEN Razon_Social
                                ELSE  COALESCE(Nombre, CONCAT_WS(" ",Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido) )
                                
                            END) AS Nombre,
                            Correo,
                             Celular, Tipo, "NIT" AS Tipo_Identificacion,
                            Digito_Verificacion, Regimen, Direccion 
                FROM Proveedor WHERE Id_Proveedor = ' . $facturaAdmin['Id_Cliente'];
                break;

            case 'Cliente':
                return getCliente($nota);
                break;

            default:

                break;
        }

        $oCon = new consulta();
        $oCon->setQuery($query);

        $cliente = $oCon->getData();
        unset($oCon);

        return $cliente;
    } else {
        return getCliente($nota);
    }
}
function getCliente($nota)
{

    global $factura;
    $query = 'SELECT "Cliente" AS Tipo_Tercero, Id_Cliente, 
                    (CASE 
                        WHEN Tipo = "Juridico" THEN Razon_Social
                        ELSE  COALESCE(Nombre, CONCAT_WS(" ",Primer_Nombre,Segundo_Nombre,Primer_Apellido,Segundo_Apellido) )
                        
                    END) AS Nombre,
                     Correo_Persona_Contacto AS Correo,
                     Celular, Tipo, Tipo_Identificacion,
                            Digito_Verificacion, Regimen, Direccion
    
         FROM Cliente WHERE Id_Cliente =' . $factura['Id_Cliente'];

    $oCon = new consulta();
    $oCon->setQuery($query);
    $cliente = $oCon->getData();
    unset($oCon);
    return $cliente;
}
function getNombre()
{
    global $resolucion, $nota_credito;
    $nit = getNit();
    $codigo = (int) str_replace($resolucion['Codigo'], "", $nota_credito['Codigo']);
    $nombre = str_pad($nit, 10, "0", STR_PAD_LEFT) . "000" . date("y") . str_pad($codigo, 8, "0", STR_PAD_LEFT);
    return $nombre;
}
