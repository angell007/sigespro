<?php
     ini_set("include_path", '/home/corvuslab/php:' . ini_get("include_path") );
	require_once($_SERVER['DOCUMENT_ROOT'].'/config/start.inc.php');
	include_once('class.lista.php');
	include_once('class.complex.php');
    include_once('class.consulta.php');
    require_once('class.qr.php'); 
    require_once('class.envio_factura_electronica.php'); 


 
    class FacturaElectronica{
        private $zip='';
      private $resolucion='', $factura='', $configuracion='', $productos='', $cliente='', $temporal='', $nombre_archivo,$zip_encode; 
        
		function __construct($tipo_factura, $id_factura,$resolucion_facturacion){
            $this->zip=new ZipArchive;
            self::getDatos($tipo_factura, $id_factura,$resolucion_facturacion);
            $nombre=self::getNombre();
            $contenido= self::getContenido();
            self::escribirArchivo($nombre, $contenido);
            $this->GetQr();
            $this->factura['Fecha']=date('c',strtotime($this->factura['Fecha_Documento']));
            

            $enviar=new EnviarFacturaElectronica();
            $enviar->Enviar($this->factura,$this->zip_encode);
            return(self::getCUFE());
		}

		function __destruct(){
			
    }
    private function getCUFE(){
        $nit=self::getNit();
        $fecha=str_replace(":","",$this->factura['Fecha_Documento']);
        $fecha=str_replace("-","",$fecha);
        $fecha=str_replace(" ","",$fecha);
        $variable=$this->factura['Codigo'].";".$fecha.";".number_format($this->temporal['Total'],2,".","").";".number_format($this->temporal['Total_Iva'],2,".","").";"."01".";".$this->temporal['Impuesto'].";".$nit.";"."O-99".$this->cliente['Id_Cliente'].";".$this->resolucion['Clave_Tecnica'];
      return hash('sha1',$variable);
    }



    private function escribirArchivo($nombre, $contenido){
      $archivo = fopen($nombre, "a");
      fwrite($archivo, $contenido);

      $nombre=str_replace('.xml','.zip',$nombre);
      $zip1= fopen($nombre,"w");
       
  
       
      if ($this->zip->open($nombre) === TRUE) {
          
        $this->zip->addFile($_SERVER['DOCUMENT_ROOT'].'/ARCHIVOS/FACTURACION_COMERCIAL/'.$this->nombre_archivo, $this->nombre_archivo);
        $this->zip->close();
       
     }
     $archivo=file_get_contents($nombre);
     $this->zip_encode=base64_encode($archivo);



    }
    private function getDatos($tipo_factura, $id_factura,$resolucion_facturacion){
        $oItem=new complex("Resolucion", "Id_Resolucion", $resolucion_facturacion);
        $this->resolucion=$oItem->getData();
        unset($oItem);

        $oItem=new complex($tipo_factura, "Id_".$tipo_factura, $id_factura );
        $this->factura=$oItem->getData();
        unset($oItem);

        $query="SELECT C.*,(SELECT D.Nombre FROM Departamento D WHERE D.Id_Departamento=C.Id_Departamento) as Departamento, (SELECT M.Nombre FROM Municipio M WHERE M.Id_Municipio=C.Id_Municipio) as Ciudad FROM Configuracion C WHERE C.Id_Configuracion=1";

        $oCon=new consulta();
        $oCon->setQuery($query);
        $this->configuracion=$oCon->getData();            
        unset($oItem);

        $query="SELECT C.*,(SELECT D.Nombre FROM Departamento D WHERE D.Id_Departamento=C.Id_Departamento) as Departamento, (SELECT M.Nombre FROM Municipio M WHERE M.Id_Municipio=C.Id_Municipio) as Ciudad FROM Cliente C WHERE C.Id_Cliente=".$this->factura['Id_Cliente'];

        $oCon=new consulta();
        $oCon->setQuery($query);
        $this->cliente=$oCon->getData();
        unset($oCon);

        $query='SELECT PF.*, IFNULL(CONCAT(P.Nombre_Comercial, " - ",P.Principio_Activo, " ", P.Cantidad,"", P.Unidad_Medida, " " , P.Presentacion," (LAB- ", P.Laboratorio_Comercial,") ", P.Invima, " CUM:", P.Codigo_Cum, " - Lote: ",PF.Lote), CONCAT(P.Nombre_Comercial, " (LAB-", P.Laboratorio_Comercial, ") - Lote: ",PF.Lote)) as Producto FROM Producto_'.$tipo_factura.' PF INNER JOIN Producto P ON PF.Id_Producto=P.Id_Producto  WHERE Id_'.$tipo_factura.'='.$id_factura;

      
        $oCon=new consulta();
        $oCon->setTipo('Multiple');
        $oCon->setQuery($query);
        $this->productos=$oCon->getData();
        unset($oCon);

       

        $query='SELECT SUM(Cantidad*Precio_Venta) as Total, SUM((Cantidad*Precio_Venta)*(Impuesto/100)) as Total_Iva, Impuesto FROM Producto_'.$tipo_factura.' WHERE Id_'.$tipo_factura.'='.$id_factura;
        $oCon=new consulta();
        $oCon->setQuery($query);
        $this->temporal=$oCon->getData();
        unset($oCon);
        
    
    }
    private function getNombre(){
      $nit=self::getNit();
      $codigo=str_replace($this->resolucion['Codigo'],"", $this->factura['Codigo']);
      $nombre="face_f".str_pad($nit, 10, "0", STR_PAD_LEFT).str_pad(dechex($codigo), 10, "0", STR_PAD_LEFT);
      $this->nombre_archivo= $nombre.'.xml';
      return $_SERVER['DOCUMENT_ROOT'].'/ARCHIVOS/FACTURACION_COMERCIAL/'.$nombre.'.xml';
    }

    
    function getNit(){

        $nit=explode("-",$this->configuracion['NIT']);
        $nit=str_replace(".","", $nit[0]);

        return $nit;
    }
    function getFecha($tipo){
        $fecha=explode(" ",$this->factura['Fecha_Documento']);
      
        if($tipo=='Fecha'){
            return $fecha[0];
        }elseif ($tipo=='Hora') {
            return $fecha[1];
        }
    }

    private function getContenido(){
      $contenido='<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
    <fe:Invoice xmlns:fe="http://www.dian.gov.co/contratos/facturaelectronica/v1" 
    xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2" 
    xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" 
    xmlns:clm54217="urn:un:unece:uncefact:codelist:specification:54217:2001" 
    xmlns:clm66411="urn:un:unece:uncefact:codelist:specification:66411:2001" 
    xmlns:clmIANAMIMEMediaType="urn:un:unece:uncefact:codelist:specification:IANAMIMEMediaType:2003" 
    xmlns:ext="urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2" 
    xmlns:qdt="urn:oasis:names:specification:ubl:schema:xsd:QualifiedDatatypes-2" 
    xmlns:sts="http://www.dian.gov.co/contratos/facturaelectronica/v1/Structures" 
    xmlns:udt="urn:un:unece:uncefact:data:specification:UnqualifiedDataTypesSchemaModule:2" 
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
    xsi:schemaLocation="http://www.dian.gov.co/contratos/facturaelectronica/v1 ../xsd/DIAN_UBL.xsd urn:un:unece:uncefact:data:specification:UnqualifiedDataTypesSchemaModule:2 ../../ubl2/common/UnqualifiedDataTypeSchemaModule-2.0.xsd urn:oasis:names:specification:ubl:schema:xsd:QualifiedDatatypes-2 ../../ubl2/common/UBL-QualifiedDatatypes-2.0.xsd">
    <ext:UBLExtensions>
       '.self::getDatosDian().self::getFirma().'
    </ext:UBLExtensions>
    <cbc:UBLVersionID>UBL 2.0</cbc:UBLVersionID>
    <cbc:ProfileID>DIAN 1.0</cbc:ProfileID>
    <cbc:ID>'.$this->factura['Codigo'].'</cbc:ID>
    <cbc:UUID schemeAgencyID="195" schemeAgencyName="CO, DIAN (Direccion de Impuestos y Aduanas
    Nacionales)">'.self::getCUFE().'</cbc:UUID>
    <cbc:IssueDate>'.self::getFecha('Fecha').'</cbc:IssueDate>
    <cbc:IssueTime>'.self::getFecha('Hora').'</cbc:IssueTime>
    <cbc:InvoiceTypeCode listAgencyID="195" listAgencyName="CO, DIAN (Direccion de Impuestos y Aduanas Nacionales)" listSchemeURI="http://www.dian.gov.co/contratos/facturaelectronica/v1/InvoiceType">1</cbc:InvoiceTypeCode>
    <cbc:Note>Set de pruebas = f-s0001_700085371_248c0_R545939-41-9715_0A_700085371 2015-06-30 sde rango #9709 hasta
    9822; numeracion del 9716 NumFac: 4171263356 FecFac: 20150626221912 ValFac: 10121904.00 CodImp1: 01 ValImp1:
    1619504.64 CodImp2: 02 ValImp2: 0.00 CodImp3: 03 ValImp3: 419046.82 ValImp: 12160455.46 NitOFE: 700085371 TipAdq:
    22 NumAdq: 8355990 String:
    41712633562015062622191210121904.00011619504.64020.0003419046.8212160455.46700085371228355990693ff6f2a
    553c3646a063436fd4dd9ded0311471</cbc:Note>
    <cbc:DocumentCurrencyCode>COP</cbc:DocumentCurrencyCode>'.self::getDatosEmpresa().self::getDatosCliente().self::getDatosProductos().'    
    </fe:Invoice>';
      return $contenido;
    }

    function getDatosCliente(){
        $texto=$this->cliente['Tipo']=="Juridico" ? '1' :'2';
      $datoscliente='
        <fe:AccountingCustomerParty>
        <cbc:AdditionalAccountID>'.$texto.'</cbc:AdditionalAccountID>
        <fe:Party>
            <cac:PartyIdentification>
                <cbc:ID schemeAgencyID="195" schemeAgencyName="CO, DIAN (Direccion de Impuestos y Aduanas Nacionales)" schemeID="22">'.$this->cliente['Id_Cliente'].'</cbc:ID>
            </cac:PartyIdentification>
            <fe:PhysicalLocation>
                <fe:Address>
                    <cbc:Department>'.$this->cliente['Departamento'].'</cbc:Department>
                    <cbc:CitySubdivisionName>'.$this->cliente['Ciudad'].'</cbc:CitySubdivisionName>
                    <cbc:CityName>'.$this->cliente['Ciudad'].'</cbc:CityName>
                    <cac:AddressLine>
                        <cbc:Line>'.$this->cliente['Direccion'].'</cbc:Line>
                    </cac:AddressLine>
                    <cac:Country>
                        <cbc:IdentificationCode>CO</cbc:IdentificationCode>
                    </cac:Country>
                </fe:Address>
            </fe:PhysicalLocation>
            <fe:PartyTaxScheme>
                <cbc:TaxLevelCode>0</cbc:TaxLevelCode>
                <cac:TaxScheme />
            </fe:PartyTaxScheme>
            '.self::getDatosTipo().'
          
        </fe:Party>
       </fe:AccountingCustomerParty>  
      ';
      return $datoscliente;
    }
    function getDatosTipo(){
        $tipo='';
        if($this->cliente['Tipo']=="Natural"){
            $tipo=' <fe:Person>
            <cbc:FirstName>'.$this->cliente['Primer_Nombre'].'</cbc:FirstName>
            <cbc:FamilyName>'.$this->cliente['Primer_Apellido'].'</cbc:FamilyName>
            <cbc:MiddleName>'.$this->cliente['Segundo_Nombre'].'</cbc:MiddleName>
             </fe:Person>';
        }else{
            $tipo='<fe:PartyLegalEntity>
            <cbc:RegistrationName>'.$this->cliente['Nombre'].'</cbc:RegistrationName>
        </fe:PartyLegalEntity>';
        }
      return $tipo;
    }
    function getDatosEmpresa(){
        $nit=explode("-",$this->configuracion['NIT']);
        $nit=str_replace(".","", $nit[0]);
        $texto=$this->configuracion['Tipo_Persona']=="Juridico" ? '1' :'2';
      $datosempresa='<fe:AccountingSupplierParty>
      <cbc:AdditionalAccountID>1</cbc:AdditionalAccountID>
      <fe:Party>
          <cac:PartyIdentification>
              <cbc:ID schemeAgencyID="195" schemeAgencyName="CO, DIAN (Direccion de Impuestos y Aduanas Nacionales)" schemeID="31">'.$nit.'</cbc:ID>
          </cac:PartyIdentification>
          <cac:PartyName>
              <cbc:Name>'.$this->configuracion['Nombre_Empresa_Dian'].'</cbc:Name>
          </cac:PartyName>
          <fe:PhysicalLocation>
              <fe:Address>
                  <cbc:Department>'.$this->configuracion['Departamento'].'</cbc:Department>
                  <cbc:CitySubdivisionName>'.$this->configuracion['Barrio'].'</cbc:CitySubdivisionName>
                  <cbc:CityName>'.$this->configuracion['Ciudad'].'</cbc:CityName>
                  <cac:AddressLine>
                      <cbc:Line>'.$this->configuracion['Direccion_Dian'].'</cbc:Line>
                  </cac:AddressLine>
                  <cac:Country>
                      <cbc:IdentificationCode>'.$this->configuracion['Pais'].'</cbc:IdentificationCode>
                  </cac:Country>
              </fe:Address>
          </fe:PhysicalLocation>
          <fe:PartyTaxScheme>
                <cbc:RegistrationName>'.$this->configuracion['Nombre_Empresa_Dian'].'</cbc:RegistrationName>
                <cbc:CompanyID schemeAgencyID="195" schemeAgencyName="CO, DIAN (Direccion de Impuestos y Aduanas Nacionales)" schemeDataURI="http://www.dian.gov.co RUT" schemeID="31" >'.$nit.'</cbc:CompanyID>
                '.self::getResponsabilidad().'
                <cac:TaxScheme/>
          </fe:PartyTaxScheme>
          <fe:PartyTaxScheme>
              <cbc:TaxLevelCode>'.$texto.'</cbc:TaxLevelCode>
              <cac:TaxScheme />
          </fe:PartyTaxScheme>
          <fe:PartyTaxScheme>
                <cbc:TaxLevelCode>R-16-PJ</cbc:TaxLevelCode>
                <cac:TaxScheme/>
          </fe:PartyTaxScheme>
          <fe:PartyLegalEntity>
              <cbc:RegistrationName>'.$this->configuracion['Nombre_Empresa_Dian'].'</cbc:RegistrationName>
          </fe:PartyLegalEntity>
      </fe:Party>
  </fe:AccountingSupplierParty>  
      ';
      return $datosempresa;
    }

    function getResponsabilidad(){
       $responsabilidad='';
       $query='SELECT * FROM Responsabilidad';
       $oCon=new Consulta();
       $oCon->setQuery($query);
       $oCon->setTipo('Multiple');
       $res=$oCon->getData();
       
       foreach ($res as  $value) {
           $responsabilidad.=' <cbc:TaxLevelCode>O-'.$value['Codigo'].'</cbc:TaxLevelCode>';
       }
      return $responsabilidad;
    }
    function getDatosDian(){
      $datosdian='<ext:UBLExtension>
          <ext:ExtensionContent>
              <sts:DianExtensions>
                  <sts:InvoiceControl>
                      <sts:InvoiceAuthorization>'.$this->resolucion['Resolucion'].'</sts:InvoiceAuthorization>
                      <sts:AuthorizationPeriod>
                          <cbc:StartDate>'.$this->resolucion['Fecha_Inicio'].'</cbc:StartDate>
                          <cbc:EndDate>'.$this->resolucion['Fecha_Fin'].'</cbc:EndDate>
                      </sts:AuthorizationPeriod>
                      <sts:AuthorizedInvoices>
                          <sts:Prefix>'.$this->resolucion['Codigo'].'</sts:Prefix>
                          <sts:From>'.$this->resolucion['Numero_Inicial'].'</sts:From>
                          <sts:To>'.$this->resolucion['Numero_Final'].'</sts:To>
                      </sts:AuthorizedInvoices>
                  </sts:InvoiceControl>
                  <sts:InvoiceSource>
                      <cbc:IdentificationCode listAgencyID="6" listAgencyName="United Nations Economic Commission for Europe" listSchemeURI="urn:oasis:names:specification:ubl:codelist:gc:CountryIdentificationCode-2.0">CO</cbc:IdentificationCode>
                  </sts:InvoiceSource>
                  <sts:SoftwareProvider>
                      <sts:ProviderID schemeAgencyID="195" schemeAgencyName="CO, DIAN (Direccion de Impuestos y Aduanas
                      Nacionales)">'.self::getNitEmpresa().'</sts:ProviderID>
                      <sts:SoftwareID schemeAgencyID="195" schemeAgencyName="CO, DIAN (Direccion de Impuestos y Aduanas
                      Nacionales)">'.$this->resolucion['Id_Software'].'</sts:SoftwareID>
                  </sts:SoftwareProvider>
                  <sts:SoftwareSecurityCode schemeAgencyID="195" schemeAgencyName="CO, DIAN (Direccion de Impuestos y Aduanas Nacionales)">'.self::getSecurityCode().'</sts:SoftwareSecurityCode>
              </sts:DianExtensions>
          </ext:ExtensionContent>
      </ext:UBLExtension>';
      return $datosdian;
    }
    function getSecurityCode(){
        return hash('sha384',$this->resolucion['Id_Software'].$this->resolucion['Pin']);
    }

    function getNitEmpresa(){
        $nit=explode("-",$this->configuracion['NIT']);
        $nit=str_replace(".","", $nit[0]);
        return $nit;
    }
    private function getDatosProductos(){
      $datosproducto='<fe:TaxTotal>
          <cbc:TaxAmount currencyID="COP">'.number_format($this->temporal['Total'],2,".","").'</cbc:TaxAmount>
          <cbc:TaxEvidenceIndicator>false</cbc:TaxEvidenceIndicator>
          <fe:TaxSubtotal>
              <cbc:TaxableAmount currencyID="COP">'.number_format($this->temporal['Total'],2,".","").'</cbc:TaxableAmount>
              <cbc:TaxAmount currencyID="COP">'.number_format($this->temporal['Total_Iva'],2,".","").'</cbc:TaxAmount>
              <cbc:Percent>'.$this->getImpuesto().'</cbc:Percent>
              <cac:TaxCategory>
                  <cac:TaxScheme>
                      <cbc:ID>01</cbc:ID>
                  </cac:TaxScheme>
              </cac:TaxCategory>
          </fe:TaxSubtotal>
      </fe:TaxTotal>
      <fe:LegalMonetaryTotal>
          <cbc:LineExtensionAmount currencyID="COP">'.number_format($this->temporal['Total'],2,".","").'</cbc:LineExtensionAmount>
          <cbc:TaxExclusiveAmount currencyID="COP">'.number_format($this->temporal['Total_Iva'],2,".","").'</cbc:TaxExclusiveAmount>
          <cbc:PayableAmount currencyID="COP">'.number_format(($this->temporal['Total_Iva']+$this->temporal['Total']),2,".","").'</cbc:PayableAmount>
      </fe:LegalMonetaryTotal>  
      '.self::getValorProductos();
      
      return $datosproducto;
    }
function getValorProductos(){
    $linea='';

    foreach ($this->productos as $key => $value) {
        $total=$value['Cantidad']*$value['Precio_Venta'];
        $impuesto='';
        if($value['Impuesto']!=0){
            $imp=($value['Cantidad']*$value['Precio_Venta'])*($value['Impuesto']/100);

            $impuesto='<cac:TaxTotal>
			<cbc:TaxAmount currencyID="COP">'.number_format($imp,2,".","").'</cbc:TaxAmount>
			<cbc:TaxEvidenceIndicator>false</cbc:TaxEvidenceIndicator>
			<cac:TaxSubtotal>
				<cbc:TaxableAmount currencyID="COP">'.number_format($total,2,".","").'</cbc:TaxableAmount>
				<cbc:TaxAmount currencyID="COP">'.number_format($imp,2,".","").'</cbc:TaxAmount>
				<cbc:Percent>'.$value['Impuesto'].'</cbc:Percent>
				<cac:TaxCategory>
					<cbc:ID>01</cbc:ID>
					<cbc:Name>IVA</cbc:Name>
					<cac:TaxScheme/>
				</cac:TaxCategory>
			</cac:TaxSubtotal>
		</cac:TaxTotal>';
        }
        $i=$key+1;
       $linea.='<fe:InvoiceLine>
        <cbc:ID>'.$i.'</cbc:ID>
        <cbc:InvoicedQuantity>'.$value['Cantidad'].'</cbc:InvoicedQuantity>
        <cbc:LineExtensionAmount currencyID="COP">'.number_format($total,2,".","").'</cbc:LineExtensionAmount>
        '.$impuesto.'  
        <fe:Item>
           <cbc:Description>'.$value['Producto'].' </cbc:Description>
        </fe:Item>
        <fe:Price>
           <cbc:PriceAmount currencyID="COP">'.number_format($value['Precio_Venta'],2,".","").'</cbc:PriceAmount>
        </fe:Price>
        </fe:InvoiceLine>';
    }

    return $linea;

}

    function getFirma(){
      $firma='<ext:UBLExtension>
        <ext:ExtensionContent>
            <ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#" Id="xmldsig-79c270e3-50bb-4fcf-b9bc-3a95bcf2466d">
                <ds:SignedInfo>
                    <ds:CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315" />
                    <ds:SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1" />
                    <ds:Reference Id="xmldsig-79c270e3-50bb-4fcf-b9bc-3a95bcf2466d-ref0" URI="">
                        <ds:Transforms>
                            <ds:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature" />
                        </ds:Transforms>
                        <ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1" />
                        <ds:DigestValue>21GME6Y4G7l+35aMpi+nzB/Di88=</ds:DigestValue>
                    </ds:Reference>
                    <ds:Reference URI="#xmldsig-87d128b5-aa31-4f0b-8e45-3d9cfa0eec26-keyinfo">
                        <ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1" />
                        <ds:DigestValue>0iE/FGZgLfbnV9DhUaDBBVPjn44=</ds:DigestValue>
                    </ds:Reference>
                    <ds:Reference Type="http://uri.etsi.org/01903#SignedProperties" URI="#xmldsig-79c270e3-50bb-4fcf-b9bc-3a95bcf2466dsignedprops">
                        <ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1" />
                        <ds:DigestValue>k/NyUxvsY6yGVV61NofEz5FaNmU=</ds:DigestValue>
                    </ds:Reference>
                </ds:SignedInfo>
                <ds:SignatureValue Id="xmldsig-79c270e3-50bb-4fcf-b9bc-3a95bcf2466dsigvalue">AvkA/W71FvZs659Id1Xrn9JMgYY1gaEVWtek/6DcqA9FvezeUPxGWCXQ07rgCSDMMdz2mX6nbp3L DscgWqVy4VIogV/zok60j92iFRjCUzUGI6MVON5G8jxX+dZkZRjFAEAwLQvoYJo/1rxLFQ+uQYZ3 kp/O+bDfQ+ybPagoDAQbU/vdrZnC9fzS7C9X0MlKqkGUIKJp+4MztMPjDmnfPKagrWo1T51N9TfA xR4KHhFDAtEDFB/55dAI3lAiI7TL5US6Ety+D1taefGj48lVsEDNo+kbe/7UcdYSiww+QX/BSpgP AV7+Zh/GdR8u+FMe/ut+WidNpZseIynWIE1uYA==</ds:SignatureValue>
                <ds:KeyInfo Id="xmldsig-87d128b5-aa31-4f0b-8e45-3d9cfa0eec26-keyinfo">
                    <ds:X509Data>
                        <ds:X509Certificate>.km.lkjmkjlk</ds:X509Certificate>
                    </ds:X509Data>
                </ds:KeyInfo>
                <ds:Object>
                    <xades:QualifyingProperties xmlns:xades="http://uri.etsi.org/01903/v1.3.2#" 
                        xmlns:xades141="http://uri.etsi.org/01903/v1.4.1#" Target="#xmldsig-79c270e3-50bb-4fcf-b9bc-3a95bcf2466d">
                        <xades:SignedProperties Id="xmldsig-79c270e3-50bb-4fcf-b9bc-3a95bcf2466d-signedprops">
                            <xades:SignedSignatureProperties>
                                <xades:SigningTime>2015-06-30T20:56:11.675-05:00</xades:SigningTime>
                                <xades:SigningCertificate>
                                    <xades:Cert>
                                        <xades:CertDigest>
                                            <ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1" />
                                            <ds:DigestValue>2el6MfWvYsvEaa/TV513a7tVK0g=</ds:DigestValue>
                                        </xades:CertDigest>
                                        <xades:IssuerSerial>
                                            <ds:X509IssuerName>C=CO,L=Bogota D.C.,O=Andes SCD.,OU=Division de certificacion entidad final,CN=CA ANDES SCD S.A.
                                            Clase II,1.2.840.113549.1.9.1=#1614696e666f40616e6465737363642e636f6d2e636f</ds:X509IssuerName>
                                            <ds:X509SerialNumber>9128602840918470673</ds:X509SerialNumber>
                                        </xades:IssuerSerial>
                                    </xades:Cert>
                                    <xades:Cert>
                                        <xades:CertDigest>
                                            <ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1" />
                                            <ds:DigestValue>YGJTXnOzmebG2Mc6A/QapNi1PRA=</ds:DigestValue>
                                        </xades:CertDigest>
                                        <xades:IssuerSerial>
                                            <ds:X509IssuerName>C=CO,L=Bogota D.C.,O=Andes SCD,OU=Division de certificacion,CN=ROOT CA ANDES SCD S.A.,1.2.840.113549.1.9.1=#1614696e666f40616e6465737363642e636f6d2e636f</ds:X509IssuerName>
                                            <ds:X509SerialNumber>7958418607150926283</ds:X509SerialNumber>
                                        </xades:IssuerSerial>
                                    </xades:Cert>
                                    <xades:Cert>
                                        <xades:CertDigest>
                                            <ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1" />
                                            <ds:DigestValue>6EVr7OINyc49AgvNkie19xul55c=</ds:DigestValue>
                                        </xades:CertDigest>
                                        <xades:IssuerSerial>
                                            <ds:X509IssuerName>C=CO,L=Bogota D.C.,O=Andes SCD,OU=Division de certificacion,CN=ROOT CA ANDES SCD S.A.,1.2.840.113549.1.9.1=#1614696e666f40616e6465737363642e636f6d2e636f</ds:X509IssuerName>
                                            <ds:X509SerialNumber>3248112716520923666</ds:X509SerialNumber>
                                        </xades:IssuerSerial>
                                    </xades:Cert>
                                </xades:SigningCertificate>
                                <xades:SignaturePolicyIdentifier>
                                    <xades:SignaturePolicyId>
                                        <xades:SigPolicyId>
                                            <xades:Identifier>http://www.facturae.es/politica_de_firma_formato_facturae/politica_de_firma_formato_facturae_v3_1.pdf</xades:Identifier></xades:SigPolicyId>
                                            <xades:SigPolicyHash>
                                                <ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1" />
                                                <ds:DigestValue>Ohixl6upD6av8N7pEvDABhEL6hM=</ds:DigestValue>
                                            </xades:SigPolicyHash>
                                        </xades:SignaturePolicyId>
                                    </xades:SignaturePolicyIdentifier>
                                    <xades:SignerRole>
                                        <xades:ClaimedRoles>
                                            <xades:ClaimedRole>supplier</xades:ClaimedRole>
                                        </xades:ClaimedRoles>
                                    </xades:SignerRole>
                                </xades:SignedSignatureProperties>
                            </xades:SignedProperties>
                        </xades:QualifyingProperties>
                </ds:Object>
            </ds:Signature>
        </ext:ExtensionContent>
    </ext:UBLExtension>';
    return $firma;
    }

    private function getImpuesto(){
        $query='SELECT * FROM Impuesto WHERE Valor>0 LIMIT 1';
        $oCon=new Consulta();
        $oCon->setQuery($query);
        $iva=$oCon->getData();

        return $iva['Valor'];
        
    }

    private function GetQr(){
        $fecha=str_replace(":","",$this->factura['Fecha_Documento']);
        $fecha=str_replace("-","",$fecha);
        $fecha=str_replace(" ","",$fecha);

        $qr="NumFac: ".$this->factura['Codigo']."\n";
        $qr.="FecFac: ".$fecha."\n";
        $qr.="NitFac: ".$this->getNit()."\n";
        $qr.="DocAdq: ".$this->factura['Id_Cliente']."\n";
        $qr.="ValFac: ".number_format($this->temporal['Total'],2,".","")."\n";
        $qr.="ValIva: ".number_format($this->temporal['Total_Iva'],2,".","")."\n";
        $qr.="ValOtroIm: 0.00 \n";
        $qr.="ValFacIm: ".number_format(($this->temporal['Total_Iva']+$this->temporal['Total']),2,".","")."\n";
        $qr.="CUFE: ".$this->getCUFE()."\n";

        $qr = generarqrFE($qr);

        
        


    }
        



    }
    

?>