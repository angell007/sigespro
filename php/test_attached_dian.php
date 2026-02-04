<?php

require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');
include_once('../class/class.facturacion_electronica.php');

$tipofactura = ( isset( $_REQUEST['tipo'] ) ? $_REQUEST['tipo'] : false );
$id_fact = ( isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : false );
$datos = ( isset( $_REQUEST['datos'] ) ? $_REQUEST['datos'] : false );

$datos = json_decode($datos, true);


error_reporting(-1);

// 19 Administrativa
// 20 Capita
// 21 NP
// 22 EP
// 23 Venta

// $fe = new FacturaElectronica($tipofactura,$id_fact, 19); 
// $datos = $fe->GenerarFactura();

$oItem = new complex($tipofactura,"Id_$tipofactura",$id_fact);
$factura = $oItem->getData();
unset($oItem);

$oItem = new complex("Configuracion","Id_Configuracion",1);
$config = $oItem->getData();
unset($oItem);

$aplication_response    =   $datos["Json"]["ResponseDian"]["Envelope"]["Body"]["SendBillSyncResponse"]["SendBillSyncResult"]["XmlBase64Bytes"];
$aplication_response    =   base64_decode($aplication_response);

$informacion = $datos["Enviado"];

header("Content-disposition: attachment; filename=ad_".$informacion["file"].".xml");

preg_match('/IssueDate>(.*?)<\/cbc:IssueDate/is', $aplication_response, $coincidencias);
$fecha = $coincidencias[1];
preg_match('/IssueTime>(.*?)<\/cbc:IssueTime/is', $aplication_response, $coincidencias2);
$hora = $coincidencias2[1];
preg_match('/ResponseCode>(.*?)<\/cbc:ResponseCode/is', $aplication_response, $coincidencias3);
$respuesta = $coincidencias3[1];

$num = explode("-",$config["NIT"]);
$nit = str_replace(".","",$num[0]);
$dv= $num[1];

$xml_factura = file_get_contents('https://sigesproph.com.co/php/facturacion_electronica/descargar_xml.php?Tipo_Factura=Factura_Administrativa&Id_Factura='.$id_fact);

//echo $xml_factura; exit;

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
<cbc:ID>'.$id.'</cbc:ID>
<cbc:IssueDate>'.$fecha.'</cbc:IssueDate>
<cbc:IssueTime>'.$hora.'</cbc:IssueTime>
<cbc:DocumentType>Contenedor de Factura Electrónica</cbc:DocumentType>
<cbc:ParentDocumentID>'.$factura["Codigo"].'</cbc:ParentDocumentID>
<cac:SenderParty>
<cac:PartyTaxScheme>
<cbc:RegistrationName>'.$config["Nombre_Empresa"].'</cbc:RegistrationName>
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
<cbc:RegistrationName>'.$informacion["customer"]["name"].'</cbc:RegistrationName>
<cbc:CompanyID schemeName="31" schemeID="'.$informacion["customer"]["dv"].'" schemeAgencyName="CO, DIAN (Dirección de Impuestos y Aduanas Nacionales)" schemeAgencyID="195">'.$informacion["customer"]["identification_number"].'</cbc:CompanyID>
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
<cbc:ID>'.$factura["Codigo"].'</cbc:ID>
<cbc:UUID schemeName="CUFE-SHA384">'.$factura["Cufe"].'</cbc:UUID>
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

echo $xml;

//echo json_encode($informacion);

?>