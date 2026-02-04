<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
//header ("Content-Type:text/xml");
//header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('../config/start.inc.php');
include_once('../class/class.lista.php');
include_once('../class/class.complex.php');
include_once('../class/class.consulta.php');
include_once('../class/class.factura_test.php');


$query = 'SELECT * FROM Factura WHERE DATE(Fecha_Documento)>"2021-04-01"  and Cufe is not null
ORDER BY Fecha_Documento
';

$oCon = new consulta();
$oCon->setQuery($query);
$oCon->setTipo('Multiple');
$data = $oCon->getData();
//var_dump($data);exit;
echo '<table>
    <thead>
        <tr>
            <th>Fecha</th>
            <th>Factura</th>
            <th>Cufe</th>
            <th>StatusDescription</th>
             <th>IsValid</th>
            <th>StatusCode</th>
            <th>StatusMessage</th>
        
        </tr>
    </thead>
    <body>';
    $x = 0; 
foreach($data as $d){
    //$fact = new FacturaElectronica('Factura', 180667, 21);
  //  var_dump ($d);
  
  $res = ConsultaCufe($d['Cufe']);
    //var_dump ($res);
  
    //if($res['ResponseDian']['Envelope']['Body']['GetStatusResponse']['GetStatusResult']['isValid'] == 'false'){
        
 
    echo '
        <tr>
            <th>'.$d['Fecha_Documento'].'</th>
            <th>'.$d['Codigo'].'</th>
            <th>'.$d['Cufe'].'</th>
            <th>'.($res["Estado"] == "error" ? $res["Error"] : $res['ResponseDian']['Envelope']['Body']['GetStatusResponse']['GetStatusResult']['StatusDescription']).'  </th>
            <th>'.$res['ResponseDian']['Envelope']['Body']['GetStatusResponse']['GetStatusResult']['IsValid'].'  </th>
            <th>'.$res['ResponseDian']['Envelope']['Body']['GetStatusResponse']['GetStatusResult']['StatusCode'].'  </th>
            <th>'.$res['ResponseDian']['Envelope']['Body']['GetStatusResponse']['GetStatusResult']['StatusMessage'].'  </th>
            
        </tr>
    ';
    if($x==15){
        $x= 0;
        sleep(5);
    }
    $x++;
  //  }
}



  function ConsultaCufe($cufe){
        
      //  var_dump($cufe);
        //$cufe = $this->factura['Cufe'];
        //return $this->factura;
         $login = 'facturacion@prohsa.com';
        $password = '804016084';
        $host = 'https://api-dian.192.168.40.201';
        $api = '/api';
        $version = '/ubl2.1';
        $modulo = '/status/document/';
        $url = $host . $api . $version . $modulo . $cufe;

       // $data = json_encode($datos);
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
 return $json_output;
            //var_dump($json_output);
 /*
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
            }*/

            return $respuesta;
        }
    }
/*
echo ' </body>
        </table>';*/