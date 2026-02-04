<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');

include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');
include_once('../class/class.facturacion_electronica.php');

$tipofactura = 'Factura_Administrativa';
$id_fact = '11182';


$fe = new FacturaElectronica($tipofactura, $id_fact, 40);
$datos = $fe->GenerarFactura();
//$api_response = $factura->getApi($datos);

$aplication_response    =   $datos["Json"]["ResponseDian"]["Envelope"]["Body"]["SendBillSyncResponse"]["SendBillSyncResult"]["XmlBase64Bytes"];
$aplication_response    =   base64_decode($aplication_response);

$oCon = new complex($tipofactura, "Id_$tipofactura", $id_fact);
$factura = $oCon->getData();
unset($oCon);

$oCon = new complex("Resolucion", "Id_Resolucion", 40);
$resolucion = $oCon->getData();
unset($oCon);

$oItem = new complex("Configuracion", "Id_Configuracion", 1);
$configuracion = $oItem->getData();
unset($oItem);


$xml_invoice = 'https://api-dian.sigesproph.com.co/api-dian/storage/app/xml/1/' . $resolucion["resolution_id"] . '/fv' . $name_file . '.xml';

readfile(getXml($aplication_response, $factura['Cufe']));



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
    $xml_invoice = 'https://api-dian.sigesproph.com.co/api-dian/storage/app/xml/1/' . $resolucion["resolution_id"] . '/fv' . $name_file . '.xml';

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
    <cbc:Description><![CDATA[' . str_replace("&#xF3;", "ó", $xml_factura) . ']]></cbc:Description>
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
    <cbc:Description><![CDATA[' . str_replace("  ", " ", $aplication_response) . ']]></cbc:Description>
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

    
    //echo $name_file;
    
    file_put_contents('/home/sigesproph/api-dian.sigesproph.com.co/api-dian/storage/app/ad/1/' . $resolucion["resolution_id"] . '/ad' . $name_file . '.xml', $xml);

    $xml_resp = '/home/sigesproph/api-dian.sigesproph.com.co/api-dian/storage/app/ad/1/' . $resolucion["resolution_id"] . '/ad' . $name_file . '.xml';

    return ($xml_resp);
}

function getNombre()
{
    global $resolucion, $factura;
    $nit = getNit();
    $codigo = (int)str_replace($resolucion['Codigo'], "", $factura['Codigo']);
    $nombre = str_pad($nit, 10, "0", STR_PAD_LEFT) . "000" . date("y") . str_pad($codigo, 8, "0", STR_PAD_LEFT);
    return $nombre;
}
function getNit()
{
    global $configuracion;
    $nit = explode("-", $configuracion['NIT']);
    $nit = str_replace(".", "", $nit[0]);
    return $nit;
}
