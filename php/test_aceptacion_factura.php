<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

header("content-type:application/json");
$cufe = isset($_REQUEST['cufe']) ? $_REQUEST['cufe'] : '';
// b91a3a80825b7eae4a301f5ee9692e00433aae7b1cd3f066b446c6a0cc40e4c95cbf4edb93d6d5b646c9f2d1f75b44b5

$url = "https://catalogo-vpfe.dian.gov.co/Document/ShowDocumentToPublic/$cufe";

$cc = curl_init($url);
curl_setopt($cc, CURLOPT_FAILONERROR, true); // Required for HTTP error codes to be reported via our call to curl_error($ch)
curl_setopt($cc, CURLOPT_RETURNTRANSFER, 1);
$url_content =curl_exec($cc);

if (curl_errno($cc)) {
    http_response_code(500);
    echo "error   ";
    echo curl_error($cc);
    exit;
}
curl_close($cc);




$dom = new DOMDocument();
libxml_use_internal_errors(true);
$dom->loadHTML($url_content, 1);
libxml_clear_errors();
$a =$dom->getElementById('html-gdoc');
// echo($a->nodeValue);


$array=explode("\n", str_replace(["\r", ""],'',  $a->nodeValue ));

$array= array_map('trim', $array);
foreach ($array as $key => $value) {
    if($array[$key]==""){
        unset($array[$key]);
    }
}
$array= array_values($array);





// $array=$array);
echo json_encode($array);



