<?php
   
   	require_once($_SERVER['DOCUMENT_ROOT'].'/config/start.inc.php');
	include_once('../class/class.lista.php');
	include_once('../class/class.complex.php');
    include_once('../class/class.consulta.php');
    require_once('../class/class.qr.php'); 
    require_once('../class/class.php_mailer.php'); 

    $username="08b33b11-c664-4d0d-baac-bf67151ba42c";
    $password=hash('sha256',"80401"); 
    
    
       
      $xml_body='<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:wcf="http://wcf.dian.colombia">
        <soap:Header xmlns:wsa="http:/www.w3.org/2005/08/addressing">
    	    <wsse:Security xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd" >
    	        <wsu:Timestamp wsu:Id="TS" >
    	            <wsu:Created></wsu:Created>
    	            <wsu:Expires></wsu:Expires>
    	        </wsu:Timestamp>
    	        <wsse:BinarySecurityToken EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary" ValueType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-x509-token-profile-1.0#X509v3" wsu:Id="CORVUS" >
    	        
    	        </wsse:BinarySecurityToken>
    	        <ds:Signature Id="SIG" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" ></<ds:Signature>
    	        
    	    </wsse:Security>
    	    <wsa:Action>http://wcf.dian.colombia/IWcfDianCustomerServices/GetStatus</wsa:Action>
    	    <wsa:To wsu:Id="ID" xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd" >https://vpfe-hab.dian.gov.co/WcfDianCustomerServices.svc</wsa:To>
        </soap:Header>
        <soap:Body>
        <wcf:GetNumberingRange>
        <wcf:accountCode>804016084</wcf:accountCode>
        <wcf:accountCodeT>804016084</wcf:accountCodeT>
        <wcf:softwareCode>08b33b11-c664-4d0d-baac-bf67151ba42c</wcf:softwareCode>
        </wcf:GetNumberingRange>
        </soap:Body>
        </soap:Envelope>';

        $xml_header = array(
        "Content-type: text/xml",
        "Accept: text/xml",
        "Cache-Control: no-cache",
        "Pragma: no-cache",
        "SOAPAction:\"http://wcf.dian.colombia/IWcfDianCustomerServices/GetNumberingRange\"", 
        "Content-length: ".strlen($xml_body),
       );
       
        $url =  "https://vpfe.dian.gov.co/WcfDianCustomerServices.svc?wsdl";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $xml_header);
        $response = curl_exec($ch); 
        
        if (curl_errno($ch)) {
            $respuesta["Estado"]="error";
            $respuesta["Error"] = '# Error : ' . curl_error($ch);
            var_dump($respuesta);
        }
        
            
        curl_close($ch);
        
        
        var_dump($response);
        
        
        
    

?>
            
            