<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

header("content-type:application/json");


$cufe = isset($_REQUEST['cufe']) ? $_REQUEST['cufe'] : '';

$cufe=strtolower($cufe);

$url = "https://catalogo-vpfe.dian.gov.co/Document/ShowDocumentToPublic/$cufe";
// $url = "https://catalogo-vpfe.dian.gov.co/document/searchqr?documentkey=$cufe";

$cc = curl_init($url);
curl_setopt($cc, CURLOPT_FAILONERROR, true); // Required for HTTP error codes to be reported via our call to curl_error($ch)
curl_setopt($cc, CURLOPT_RETURNTRANSFER, 1);
$url_content = curl_exec($cc);

if (curl_errno($cc)) {
    http_response_code(404);
    echo "error   ";
    echo curl_error($cc);
    exit;
}
curl_close($cc);


$dom = new DOMDocument();
libxml_use_internal_errors(true);
$dom->loadHTML($url_content, 1);
libxml_clear_errors();
$a = $dom->getElementById('html-gdoc');




$respuesta=[];
$array = explode("\n", str_replace(["\r", ""], '', $a->nodeValue));

$array = array_map('trim', $array);
foreach ($array as $key => $value) {
    if ($value == "") {
        unset($array[$key]);
    }
}
$array = array_values($array);
foreach ($array as $key => $value) {
      if($value=="CUFE:"){
            $respuesta['CUFE']=$array[$key+1];
      }
      else if(strpos($value, 'Folio')!==false){
            $valores=explode('Folio:',$value);
            $folio=trim($valores[1]);
            $valores=explode('Serie:',$valores[0]);
            $prefijo=count($valores)>1?trim($valores[1]):'';
            $respuesta['Factura']['Prefijo']=$prefijo;
            $respuesta['Factura']['Consecutivo']=$folio;
      }
      else if(strpos($value, 'EMISOR')!==false){
            $nit=trim(explode('NIT:',$array[$key+1])[1]);
            $nombre=trim(explode('Nombre:',$array[$key+2])[1]);
            $respuesta['Proveedor']['NIT']=$nit;
            $respuesta['Proveedor']['Nombre']=$nombre;
      }
      else if(strpos($value, 'RECEPTOR')!==false){
            $nit=trim(explode('NIT:',$array[$key+1])[1]);
            $nombre=trim(explode('Nombre:',$array[$key+2])[1]);
            $respuesta['Cliente']['NIT']=$nit;
            $respuesta['Cliente']['Nombre']=$nombre;
      }
}

echo json_encode($respuesta);

