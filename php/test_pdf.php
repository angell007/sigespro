<?php

//phpinfo();


$ruta = '/home/sigesproph/public_html/DIS2025/UNO.pdf';
$id_dis=1380026;
$url =  "https://sigesproph.com.co/php/dispensaciones/dispensacion_pdf.php?id={$id_dis}&Ruta={$ruta}";

echo $url;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_VERBOSE, true);              // para ver más datos
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);      // solo prueba
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);      // solo prueba
$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

//$resultado = file_get_contents($url, true);

var_dump($response);
var_dump($error);

?>