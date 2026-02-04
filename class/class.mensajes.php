<?php

 class Mensaje {

    function __construct(){			
    }

    function __destruct(){			
    }
    
    private function after ($esto, $inthat)
    {
        if (!is_bool(strpos($inthat, $esto)))
        return substr($inthat, strpos($inthat,$esto)+strlen($esto));
    }

    private function after_last ($esto, $inthat)
    {
        if (!is_bool(strrevpos($inthat, $esto)))
        return substr($inthat, strrevpos($inthat, $esto)+strlen($esto));
    }

    private function before ($esto, $inthat)
    {
        return substr($inthat, 0, strpos($inthat, $esto));
    }

    private function before_last ($esto, $inthat)
    {
        return substr($inthat, 0, strrevpos($inthat, $esto));
    }

    private function between ($esto, $that, $inthat)
    {
        return self::before($that, self::after($esto, $inthat));
    }

    private function between_last ($esto, $that, $inthat)
    {
     return self::after_last($esto, self::before_last($that, $inthat));
    }
  

    public function Enviar($numero,$mensaje){
      $login='w503';
      $password='Pr0Hs4c9';
        
      $xml_post_string = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tgg="http://ws.tiaxa.net/tggDataSoapService/">
                           <soapenv:Header/>
                           <soapenv:Body>
                              <tgg:sendMessageRequest>
                                 <subscriber>57'.$numero.'</subscriber>
                                 <sender>5787523</sender>
                                 <requestId>2</requestId>
                                 <receiptRequest>0</receiptRequest>
                                 <dataCoding>0</dataCoding>
                                 <message>'.$mensaje.'</message>
                              </tgg:sendMessageRequest>
                           </soapenv:Body>
                        </soapenv:Envelope>';   
       $headers = array(
                    "Content-type: text/xml;charset=\"utf-8\"",
                    "Accept: text/xml",
                    "Cache-Control: no-cache",
                    "Authorization: Basic ". base64_encode($login.':'.$password),
                    "Pragma: no-cache",
                    "SOAPAction:\"http://ws.tiaxa.net/tggDataSoapService/sendMessage\"", 
                    "Content-length: ".strlen($xml_post_string),
                );

        $url =  "https://www.gestormensajeriaadmin.com/RA/tggDataSoap?wsdl";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_post_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch); 
        curl_close($ch);
        
        $res = (INT)self::between('<resultCode>', '</resultCode>', $response); 
        
        if($res==0){
        	return true;
        }else{
        	return false;
        }
        
 
    }
 }